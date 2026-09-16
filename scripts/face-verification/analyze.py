#!/usr/bin/env python3
"""Moteur d'analyse OCR + vivacité + correspondance faciale d'un document
d'identité, invoqué depuis Laravel via FaceVerificationScriptClient (voir
app/Services/FaceVerification/).

Contrat d'E/S (voir config/faceverification.php et le plan associé) :
  argv (mode single-frame, utilisé par le flux Document/AnalyzeDocumentJob) :
    <document_type: cni|passport> <front_photo_path> <back_photo_path|""> <selfie_path>
  argv (mode vivacité active 3-frames, utilisé par le flux session partenaire
  QR — voir PartnerVerificationSession/AnalyzePartnerSessionJob) :
    <document_type> <front_photo_path> <back_photo_path|""> <selfie_center_path>
    <selfie_left_path> <selfie_right_path>
  stdout : UNE seule ligne JSON (voir build_output() plus bas)
  stderr : logs uniquement
  exit 0 : l'analyse a pu tourner (même si le verdict métier est négatif)
  exit != 0 : échec technique réel (dépendance manquante, image illisible...)

IMPORTANT — non testé en conditions réelles à l'écriture de ce script : les
heuristiques d'extraction OCR par champ (regex ci-dessous) sont des points de
départ raisonnables, PAS calibrées sur de vrais échantillons de CIN/passeport
haïtiens (voir le plan : échantillons demandés à l'utilisateur, pas encore
reçus). À ajuster une fois de vrais exemples disponibles.

IMPORTANT — la vivacité active (mode 3-frames) est également une heuristique
de première version, à valider sur de vrais échantillons : elle mesure une
rotation de tête gauche/centre/droite via la position relative du nez sur les
landmarks mediapipe (proxy simple, pas une estimation de pose 3D complète par
solvePnP). Les seuils ACTIVE_LIVENESS_* ci-dessous sont des points de départ
raisonnables, à ajuster une fois testés en conditions réelles.
"""

import sys
import json
import re
import traceback
import contextlib


def log(message: str) -> None:
    print(message, file=sys.stderr)


def extract_ocr_fields(document_type: str, front_photo_path: str) -> dict:
    """Retourne {"document_number": ?str, "full_name": ?str, "date_of_birth": ?str}.

    Champ "date_of_birth" toujours normalisé en YYYY-MM-DD si trouvé.
    """
    fields = {"document_number": None, "full_name": None, "date_of_birth": None}

    try:
        import easyocr
    except ImportError:
        log("easyocr n'est pas installé — extraction OCR ignorée.")
        return fields

    try:
        # Français (langue administrative d'Haïti) + anglais (souvent présent
        # sur les passeports, mentions bilingues).
        reader = easyocr.Reader(["fr", "en"], gpu=False)
        raw_results = reader.readtext(front_photo_path, detail=1)
    except Exception as exc:  # noqa: BLE001 - on ne veut jamais crasher tout le script pour l'OCR seul
        log(f"echec OCR: {exc}")
        return fields

    lines = [text.strip() for (_bbox, text, _confidence) in raw_results if text.strip()]
    full_text = " ".join(lines)

    # --- Numéro de document ---
    if document_type == "cni":
        # NIU : exactement 10 chiffres consécutifs (même règle que côté SagaID/SwapLajan).
        match = re.search(r"\b\d{10}\b", full_text.replace(" ", ""))
        if match:
            fields["document_number"] = match.group(0)
    else:
        # Passeport : bloc alphanumérique 6-20 caractères, en majuscules — heuristique
        # large, à affiner une fois de vrais passeports haïtiens disponibles.
        match = re.search(r"\b[A-Z0-9]{6,20}\b", full_text.upper())
        if match:
            fields["document_number"] = match.group(0)

    # --- Date de naissance --- (formats jj/mm/aaaa, jj-mm-aaaa, jj.mm.aaaa)
    date_match = re.search(r"\b(\d{2})[./-](\d{2})[./-](\d{4})\b", full_text)
    if date_match:
        day, month, year = date_match.groups()
        fields["date_of_birth"] = f"{year}-{month}-{day}"

    # --- Nom complet --- heuristique grossière : la ligne la plus longue composée
    # uniquement de lettres/espaces en majuscules (typique d'un nom imprimé en
    # capitales sur une pièce d'identité). À revoir avec de vrais échantillons :
    # la position/le préfixe ("NOM:", "PRENOM:") sera probablement plus fiable.
    name_candidates = [
        line for line in lines
        if re.fullmatch(r"[A-ZÀ-Ÿ' -]{4,}", line.upper()) and not re.search(r"\d", line)
    ]
    if name_candidates:
        fields["full_name"] = max(name_candidates, key=len).upper()

    return fields


def check_passive_liveness(selfie_path: str) -> tuple:
    """Retourne (liveness_passed: ?bool, warnings: list[str]).

    Détection anti-spoofing passive sur une seule image (MiniFASNet, embarqué
    dans DeepFace depuis 0.0.90+, exposé via extract_faces(anti_spoofing=True)).
    """
    warnings: list[str] = []
    liveness_passed = None

    try:
        from deepface import DeepFace
    except ImportError:
        log("deepface n'est pas installé — vivacité passive ignorée.")
        warnings.append("deepface_unavailable")
        return liveness_passed, warnings

    try:
        faces = DeepFace.extract_faces(
            img_path=selfie_path,
            anti_spoofing=True,
            enforce_detection=True,
        )
        if faces:
            # S'il y a plusieurs visages détectés, le plus grand (probablement
            # le sujet principal) fait foi.
            main_face = max(faces, key=lambda f: f.get("facial_area", {}).get("w", 0))
            liveness_passed = bool(main_face.get("is_real", False))
        else:
            warnings.append("no_face_detected_in_selfie")
    except ValueError as exc:
        # DeepFace lève parfois une ValueError explicite "Spoof detected" selon
        # la version — dans ce cas précis, on sait que c'est bien un échec de
        # vivacité, pas une panne technique.
        if "spoof" in str(exc).lower():
            liveness_passed = False
        else:
            warnings.append(f"liveness_check_error: {exc}")
    except Exception as exc:  # noqa: BLE001
        warnings.append(f"liveness_check_error: {exc}")

    return liveness_passed, warnings


def check_face_match(front_photo_path: str, selfie_path: str) -> tuple:
    """Retourne (face_match_score: ?float, warnings: list[str]).

    model_name="SFace" : modèle volontairement léger (quelques Mo, backend
    ONNX) plutôt que le VGG-Face par défaut de DeepFace (~580 Mo de poids +
    TensorFlow complet) — le serveur de déploiement est une VM partagée à RAM
    très limitée (~3.8 Go, plusieurs autres apps), où VGG-Face a provoqué un
    OOM-kill du process en conditions réelles.
    """
    warnings: list[str] = []
    face_match_score = None

    try:
        from deepface import DeepFace
    except ImportError:
        log("deepface n'est pas installé — correspondance ignorée.")
        warnings.append("deepface_unavailable")
        return face_match_score, warnings

    try:
        result = DeepFace.verify(
            img1_path=front_photo_path,
            img2_path=selfie_path,
            model_name="SFace",
            enforce_detection=True,
        )
        distance = result.get("distance")
        threshold = result.get("threshold")
        if distance is not None and threshold not in (None, 0):
            # Score de similarité normalisé 0-1 (1 = correspondance parfaite),
            # dérivé de la distance/seuil renvoyés par DeepFace plutôt que du
            # seul booléen "verified", pour donner un signal continu à l'admin.
            face_match_score = max(0.0, min(1.0, 1 - (distance / (threshold * 2))))
    except Exception as exc:  # noqa: BLE001
        warnings.append(f"face_match_error: {exc}")

    return face_match_score, warnings


def analyze_face(front_photo_path: str, selfie_path: str) -> tuple:
    """Mode single-frame (flux Document/AnalyzeDocumentJob, inchangé).

    Retourne (face_match_score: ?float, liveness_passed: ?bool, warnings: list[str]).
    """
    liveness_passed, liveness_warnings = check_passive_liveness(selfie_path)
    face_match_score, match_warnings = check_face_match(front_photo_path, selfie_path)
    return face_match_score, liveness_passed, liveness_warnings + match_warnings


# --- Vivacité active (mode 3-frames, flux session partenaire QR) ---

# Décalage horizontal normalisé du nez au-delà duquel on considère que la tête
# est tournée (proxy simple, pas une pose 3D — voir avertissement en tête de
# fichier). Point de départ raisonnable, à ajuster une fois testé en réel.
ACTIVE_LIVENESS_TURN_THRESHOLD = 0.12
# En dessous de ce seuil, le frame "centre" est considéré comme suffisamment
# frontal (tête non tournée) pour servir de référence de correspondance.
ACTIVE_LIVENESS_CENTER_THRESHOLD = 0.08

# Indices de landmarks mediapipe FaceMesh (topologie 468 points) : bord du
# visage côté gauche/droite de l'image et pointe du nez.
_MEDIAPIPE_LEFT_FACE_EDGE = 234
_MEDIAPIPE_RIGHT_FACE_EDGE = 454
_MEDIAPIPE_NOSE_TIP = 1


def _estimate_horizontal_nose_offset(image_path: str):
    """Retourne un flottant (proxy de rotation de tête, signe arbitraire mais
    cohérent d'un appel à l'autre) ou None si aucun visage n'est détecté.

    Calcule la position du nez par rapport au centre du visage, normalisée par
    la largeur du visage : proche de 0 quand la personne fait face à la
    caméra, s'éloigne de 0 (signe selon le sens) quand la tête est tournée.
    """
    import cv2
    import mediapipe as mp

    image = cv2.imread(image_path)
    if image is None:
        return None

    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

    with mp.solutions.face_mesh.FaceMesh(
        static_image_mode=True,
        max_num_faces=1,
        refine_landmarks=False,
        min_detection_confidence=0.5,
    ) as face_mesh:
        result = face_mesh.process(rgb_image)

    if not result.multi_face_landmarks:
        return None

    landmarks = result.multi_face_landmarks[0].landmark
    left_x = landmarks[_MEDIAPIPE_LEFT_FACE_EDGE].x
    right_x = landmarks[_MEDIAPIPE_RIGHT_FACE_EDGE].x
    nose_x = landmarks[_MEDIAPIPE_NOSE_TIP].x

    face_width = right_x - left_x
    if face_width == 0:
        return None

    center_x = (left_x + right_x) / 2
    return (nose_x - center_x) / face_width


def check_active_liveness(selfie_left_path: str, selfie_center_path: str, selfie_right_path: str) -> tuple:
    """Retourne (active_liveness_passed: ?bool, warnings: list[str]).

    Vérifie une transition plausible gauche -> centre -> droite entre les 3
    frames (et pas 3 photos identiques/statiques d'une même image). Le sens
    réel ("gauche" vs "droite" du point de vue de la caméra) n'a pas besoin
    d'être connu à l'avance : on vérifie juste que les deux frames de rotation
    sont de part et d'autre du centre, au-delà d'un seuil.
    """
    warnings: list[str] = []

    try:
        import mediapipe  # noqa: F401
    except ImportError:
        log("mediapipe n'est pas installé — vivacité active ignorée.")
        warnings.append("mediapipe_unavailable")
        return None, warnings

    offsets = {}
    for label, path in (("left", selfie_left_path), ("center", selfie_center_path), ("right", selfie_right_path)):
        try:
            offset = _estimate_horizontal_nose_offset(path)
        except Exception as exc:  # noqa: BLE001
            warnings.append(f"active_liveness_error_{label}: {exc}")
            return None, warnings

        if offset is None:
            warnings.append(f"active_liveness_no_face_in_{label}_frame")
            return None, warnings

        offsets[label] = offset

    center_ok = abs(offsets["center"]) < ACTIVE_LIVENESS_CENTER_THRESHOLD
    left_turned = abs(offsets["left"]) > ACTIVE_LIVENESS_TURN_THRESHOLD
    right_turned = abs(offsets["right"]) > ACTIVE_LIVENESS_TURN_THRESHOLD
    opposite_sides = (offsets["left"] > 0) != (offsets["right"] > 0)

    active_liveness_passed = center_ok and left_turned and right_turned and opposite_sides

    if not active_liveness_passed:
        warnings.append("active_liveness_no_head_turn_detected")

    return active_liveness_passed, warnings


def _face_bounding_box(image_path: str):
    """(min_x, min_y, max_x, max_y) normalisé 0-1, ou None si aucun visage détecté."""
    import cv2
    import mediapipe as mp

    image = cv2.imread(image_path)
    if image is None:
        return None

    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

    with mp.solutions.face_mesh.FaceMesh(
        static_image_mode=True,
        max_num_faces=1,
        refine_landmarks=False,
        min_detection_confidence=0.5,
    ) as face_mesh:
        result = face_mesh.process(rgb_image)

    if not result.multi_face_landmarks:
        return None

    landmarks = result.multi_face_landmarks[0].landmark
    xs = [lm.x for lm in landmarks]
    ys = [lm.y for lm in landmarks]

    return min(xs), min(ys), max(xs), max(ys)


def _detect_hand_landmarks(image_path: str) -> list:
    """Une liste de jeux de landmarks (un par main détectée), vide si aucune —
    mediapipe.solutions.hands, même famille "legacy" que face_mesh : modèles
    embarqués dans le paquet, pas de fichier .task à télécharger séparément.
    """
    import cv2
    import mediapipe as mp

    image = cv2.imread(image_path)
    if image is None:
        return []

    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

    with mp.solutions.hands.Hands(
        static_image_mode=True,
        max_num_hands=2,
        min_detection_confidence=0.5,
    ) as hands:
        result = hands.process(rgb_image)

    return result.multi_hand_landmarks or []


# Une main est considérée comme "sur le visage" seulement si une bonne partie
# de ses 21 points tombe dans la boîte englobante du visage (pas juste un
# doigt qui frôle le bord du cadre) — réduit les faux positifs (main qui
# rajuste une mèche de cheveux, repose près du menton sans le couvrir...).
HAND_OCCLUSION_MIN_POINTS_INSIDE = 6

# Rétrécit légèrement la boîte du visage testée : une main doit vraiment
# empiéter sur le visage, pas juste toucher son contour extérieur.
HAND_OCCLUSION_BOX_MARGIN_RATIO = 0.08


def check_hand_occlusion(labeled_paths: list) -> tuple:
    """labeled_paths : [(label, path), ...]. Retourne (occlusion_detected: bool,
    warnings: list[str]) — True si une main recouvre significativement le
    visage sur au moins une des images fournies. Demande explicite : un
    visage partiellement caché par la main ne doit jamais passer la
    vérification, même si les autres contrôles (rotation, correspondance)
    seraient par ailleurs satisfaits.
    """
    warnings: list[str] = []
    occlusion_detected = False

    try:
        import mediapipe  # noqa: F401
    except ImportError:
        log("mediapipe n'est pas installé — détection de main sur le visage ignorée.")
        warnings.append("hand_occlusion_check_unavailable")
        return False, warnings

    for label, path in labeled_paths:
        try:
            face_box = _face_bounding_box(path)
            if face_box is None:
                continue  # absence de visage déjà signalée ailleurs

            min_x, min_y, max_x, max_y = face_box
            margin_x = (max_x - min_x) * HAND_OCCLUSION_BOX_MARGIN_RATIO
            margin_y = (max_y - min_y) * HAND_OCCLUSION_BOX_MARGIN_RATIO
            min_x, max_x = min_x + margin_x, max_x - margin_x
            min_y, max_y = min_y + margin_y, max_y - margin_y

            for hand_landmarks in _detect_hand_landmarks(path):
                points_inside = sum(
                    1 for lm in hand_landmarks.landmark
                    if min_x <= lm.x <= max_x and min_y <= lm.y <= max_y
                )
                if points_inside >= HAND_OCCLUSION_MIN_POINTS_INSIDE:
                    occlusion_detected = True
                    warnings.append(f"hand_occlusion_detected_in_{label}_frame")
                    break
        except Exception as exc:  # noqa: BLE001 - jamais crasher tout le script pour ce contrôle seul
            warnings.append(f"hand_occlusion_check_error_{label}: {exc}")

    return occlusion_detected, warnings


def analyze_face_active(
    front_photo_path: str,
    selfie_left_path: str,
    selfie_center_path: str,
    selfie_right_path: str,
) -> tuple:
    """Mode vivacité active 3-frames (flux session partenaire QR).

    Retourne (face_match_score: ?float, liveness_passed: ?bool, warnings: list[str]).
    Le frame "centre" sert à la fois de référence de correspondance (le plus
    proche d'une pose frontale) et de base pour la vérification passive
    (MiniFASNet) — combinée à la vérification active de rotation de tête et à
    l'absence de main détectée sur le visage sur les 3 frames, liveness_passed
    n'est vrai que si TOUTES ces vérifications le sont.
    """
    passive_passed, passive_warnings = check_passive_liveness(selfie_center_path)
    active_passed, active_warnings = check_active_liveness(selfie_left_path, selfie_center_path, selfie_right_path)
    occlusion_detected, occlusion_warnings = check_hand_occlusion([
        ("left", selfie_left_path),
        ("center", selfie_center_path),
        ("right", selfie_right_path),
    ])
    face_match_score, match_warnings = check_face_match(front_photo_path, selfie_center_path)

    if passive_passed is None or active_passed is None:
        liveness_passed = None
    else:
        liveness_passed = bool(passive_passed and active_passed and not occlusion_detected)

    return (
        face_match_score,
        liveness_passed,
        passive_warnings + active_warnings + occlusion_warnings + match_warnings,
    )


def main() -> int:
    if len(sys.argv) not in (5, 7):
        log(
            "Usage: analyze.py <document_type> <front_photo_path> <back_photo_path|\"\"> <selfie_path>\n"
            "   ou: analyze.py <document_type> <front_photo_path> <back_photo_path|\"\"> "
            "<selfie_center_path> <selfie_left_path> <selfie_right_path>"
        )
        return 1

    document_type, front_photo_path, _back_photo_path, selfie_path = sys.argv[1:5]

    # Le contrat d'E/S exige un stdout composé d'UNE seule ligne JSON (voir
    # docstring en tête de fichier) — mais deepface/easyocr/mediapipe
    # impriment parfois eux-mêmes sur stdout (ex. progression de
    # téléchargement d'un modèle au premier lancement, comme MiniFASNet pour
    # l'anti-spoofing), ce qui corrompt la sortie et casse le parsing JSON
    # côté PHP (FaceVerificationScriptClient). Plutôt que de compter sur le
    # bon comportement de chaque dépendance, on redirige explicitement stdout
    # vers stderr pendant toute la durée de l'analyse, et on ne réutilise le
    # vrai stdout que pour l'unique print() final ci-dessous.
    real_stdout = sys.stdout

    with contextlib.redirect_stdout(sys.stderr):
        try:
            ocr_fields = extract_ocr_fields(document_type, front_photo_path)
        except Exception:  # noqa: BLE001 - jamais crasher sur l'OCR seul
            log(traceback.format_exc())
            ocr_fields = {"document_number": None, "full_name": None, "date_of_birth": None}

        if len(sys.argv) == 7:
            selfie_left_path, selfie_right_path = sys.argv[5:7]
            face_match_score, liveness_passed, warnings = analyze_face_active(
                front_photo_path, selfie_left_path, selfie_path, selfie_right_path
            )
        else:
            face_match_score, liveness_passed, warnings = analyze_face(front_photo_path, selfie_path)

    output = {
        "ocr": ocr_fields,
        "liveness_passed": liveness_passed,
        "face_match_score": face_match_score,
        "warnings": warnings,
    }

    print(json.dumps(output), file=real_stdout)
    return 0


if __name__ == "__main__":
    sys.exit(main())
