#!/usr/bin/env python3
"""Moteur d'analyse OCR + vivacité + correspondance faciale d'un document
d'identité, invoqué depuis Laravel via FaceVerificationScriptClient (voir
app/Services/FaceVerification/).

Contrat d'E/S (voir config/faceverification.php et le plan associé) :
  argv (mode single-frame, utilisé par le flux Document/AnalyzeDocumentJob) :
    <document_type: cni|passport> <front_photo_path> <back_photo_path|""> <selfie_path>
  argv (mode vivacité active 3-frames, utilisé par le flux session partenaire
  QR — voir PartnerVerificationSession/AnalyzePartnerSessionJob) :
    <document_type: national_id|passport|drivers_license> <front_photo_path>
    <back_photo_path|""> <selfie_center_path> <selfie_left_path> <selfie_right_path>
  Note : "cni" et "national_id" désignent le même type de pièce (carte
  d'identité nationale) sous deux noms différents selon le flux appelant —
  extract_ocr_fields() accepte les deux.
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
import os
import json
import re
import traceback
import contextlib


def log(message: str) -> None:
    print(message, file=sys.stderr)


# Une vraie photo de téléphone (souvent 3000-4000px de large) a provoqué un
# OOM-kill d'EasyOCR en conditions réelles sur ce serveur à RAM limitée —
# confirmé en testant avec un vrai échantillon de carte d'identité. Le texte
# d'une pièce d'identité reste lisible bien en dessous de cette résolution ;
# rétrécir avant l'OCR borne l'empreinte mémoire quelle que soit la photo
# envoyée, plutôt que de dépendre de la taille choisie par le téléphone de
# l'utilisateur.
OCR_MAX_IMAGE_DIMENSION = 1600


def _load_image_for_ocr(image_path: str):
    """Charge l'image et la redimensionne si nécessaire (garde le ratio
    d'aspect, ne réduit jamais — seulement si trop grande)."""
    from PIL import Image
    import numpy as np

    image = Image.open(image_path).convert("RGB")
    width, height = image.size
    longest_side = max(width, height)

    if longest_side > OCR_MAX_IMAGE_DIMENSION:
        scale = OCR_MAX_IMAGE_DIMENSION / longest_side
        image = image.resize((int(width * scale), int(height * scale)), Image.LANCZOS)

    return np.array(image)


def _ocr_line_boxes(raw_results) -> list:
    """Convertit la sortie brute d'EasyOCR en une liste de dicts
    {x_min,y_min,x_max,y_max,text}, un par ligne détectée."""
    boxes = []
    for bbox, text, _confidence in raw_results:
        text = text.strip()
        if not text:
            continue
        xs = [p[0] for p in bbox]
        ys = [p[1] for p in bbox]
        boxes.append({"x_min": min(xs), "y_min": min(ys), "x_max": max(xs), "y_max": max(ys), "text": text})
    return boxes


# Vocabulaire de TOUTES les étiquettes de la carte (français + créole,
# fragments tolérants à la casse/aux accents/au garbling OCR) — utilisé pour
# qu'une recherche de valeur ne prenne jamais par erreur une AUTRE étiquette
# empilée juste en dessous. Très fréquent sur cette carte : chaque champ a sa
# version française puis créole l'une sous l'autre, et laquelle des deux
# s'aligne horizontalement avec la vraie valeur varie d'un champ à l'autre
# (parfois la française, parfois la créole) — confirmé en traçant un vrai
# échantillon. Exclure tout texte "qui ressemble à une étiquette connue" est
# plus robuste que de deviner un ordre fixe.
_CNI_LABEL_KEYWORDS = [
    "naissa", "lieu", "kote", "identification", "idantifikasyon", "identifikasyon",
    "énom", "non", "siyati", "sexe", "seks", "nationalit", "nasyonal",
    "emission", "émission", "expiration", "signature", "titulaire", "kat",
]


def _looks_like_label(text: str) -> bool:
    normalized = text.lower()
    return any(kw in normalized for kw in _CNI_LABEL_KEYWORDS)


def _find_label_line(lines: list, must_contain: list, must_not_contain: list = (), prefer_last: bool = False):
    """Ligne (texte normalisé : minuscule) contenant au moins un des mots-clés
    de must_contain et aucun de must_not_contain — la première par défaut, la
    dernière si prefer_last (nécessaire pour "identification" : le titre du
    document, "CARTE D'IDENTIFICATION NATIONALE", contient aussi ce mot et
    apparaît AVANT la vraie étiquette du champ NIU plus bas sur la carte)."""
    match = None
    for line in lines:
        normalized = line["text"].lower()
        if any(bad in normalized for bad in must_not_contain):
            continue
        if any(kw in normalized for kw in must_contain):
            if not prefer_last:
                return line
            match = line
    return match


def _value_candidates_below(lines: list, label_line: dict, max_y_gap: float = 150) -> list:
    """Lignes EN DESSOUS de label_line, dans la même colonne (chevauchement
    horizontal), triées par proximité verticale — jamais une autre étiquette
    connue (voir _looks_like_label).

    Nécessaire car l'ordre de détection d'EasyOCR suit grossièrement la
    position verticale sur toute la largeur de l'image, PAS l'ordre de
    lecture visuel d'une mise en page à deux colonnes : sur un vrai
    échantillon de CIN haïtienne, l'étiquette et sa valeur ne sont presque
    jamais des lignes adjacentes dans la liste brute (une ou deux lignes de
    l'autre colonne — ou l'étiquette créole du même champ — s'intercalent
    presque toujours entre les deux).

    Tolère un léger chevauchement vertical négatif (jusqu'à -20px) entre
    l'étiquette et sa valeur : les boîtes EasyOCR de deux lignes empilées se
    chevauchent souvent de quelques pixels en pratique, confirmé sur un vrai
    échantillon (jamais un écart net positif).
    """
    candidates = []
    for line in lines:
        if line is label_line or _looks_like_label(line["text"]):
            continue
        overlap = min(line["x_max"], label_line["x_max"]) - max(line["x_min"], label_line["x_min"])
        if overlap <= 0:
            continue
        y_gap = line["y_min"] - label_line["y_max"]
        if -20 <= y_gap <= max_y_gap:
            candidates.append((y_gap, line))

    candidates.sort(key=lambda c: c[0])
    return [c[1] for c in candidates]


def _find_value_below(lines: list, label_line: dict):
    candidates = _value_candidates_below(lines, label_line)
    return candidates[0]["text"] if candidates else None


def _find_row_below(lines: list, label_line: dict, row_tolerance: float = 20):
    """Comme _find_value_below, mais rassemble TOUTES les lignes de la même
    "rangée" (écart vertical proche du plus proche trouvé) et les joint de
    gauche à droite — pour une valeur écrite sur plusieurs morceaux côte à
    côte (ex. "OUEST" + "PORT-AU-PRINCE" pour le lieu de naissance, détectés
    comme deux lignes séparées à la même hauteur)."""
    candidates = _value_candidates_below(lines, label_line)
    if not candidates:
        return None

    closest_gap = candidates[0]["y_min"] - label_line["y_max"]
    same_row = [
        c for c in candidates
        if abs((c["y_min"] - label_line["y_max"]) - closest_gap) <= row_tolerance
    ]
    same_row.sort(key=lambda l: l["x_min"])
    return " ".join(l["text"] for l in same_row)


def _parse_date(value: str):
    """jj-mm-aaaa / jj/mm/aaaa / jj.mm.aaaa -> aaaa-mm-jj, ou None."""
    if not value:
        return None
    match = re.search(r"(\d{2})[./-](\d{2})[./-](\d{4})", value)
    if not match:
        return None
    day, month, year = match.groups()
    return f"{year}-{month}-{day}"


def _extract_cni_fields(lines: list) -> dict:
    """Carte d'identification nationale haïtienne — étiquettes bilingues
    (français/créole) ancrées par position, calibré sur un vrai échantillon
    (voir le plan associé). Les LIGNES d'étiquette sont souvent mal lues par
    l'OCR (accents, mots tronqués) ; les VALEURS elles-mêmes (dates, nom,
    numéro) se sont lues avec une précision parfaite sur l'échantillon testé
    — d'où des mots-clés d'étiquette volontairement tolérants (sous-chaînes
    courtes) plutôt que des libellés exacts.
    """
    fields = {
        "document_number": None,
        "full_name": None,
        "date_of_birth": None,
        "sex": None,
        "place_of_birth": None,
        "date_of_issue": None,
        "date_of_expiry": None,
    }

    # "Lieu de Naissance" contient aussi "naissance" — exclu explicitement
    # pour ne pas confondre les deux champs.
    dob_label = _find_label_line(lines, must_contain=["naissa"], must_not_contain=["lieu"])
    if dob_label:
        fields["date_of_birth"] = _parse_date(_find_value_below(lines, dob_label))

    # "identification" seul (pas "identification unique") : sur l'échantillon
    # testé, l'OCR a lu "unique" en "unicue" — "identification" reste lisible
    # tel quel. Le mot-clé apparaît aussi dans le titre du document ("CARTE
    # D'IDENTIFICATION NATIONALE"), plus haut sur la carte, mais son
    # étiquette de champ (au singulier "identification" comme sous-chaîne)
    # n'a pas de valeur alignée juste en dessous à cet endroit — sans effet.
    niu_label = _find_label_line(
        lines,
        must_contain=["identification", "idantifikasyon", "identifikasyon"],
        prefer_last=True,
    )
    if niu_label:
        value = _find_value_below(lines, niu_label)
        if value:
            digits = re.sub(r"\D", "", value)
            if len(digits) == 10:
                fields["document_number"] = digits

    # "Non" (créole, "Prénom") et "Siyati" (créole, "Nom") : mots-clés
    # distinctifs qui n'apparaissent nulle part ailleurs sur la carte,
    # contrairement à "nom"/"prénom" en français (collisions : "Prénom"
    # contient "nom" comme sous-chaîne, "Nationalité" est proche de "nom").
    first_name_label = _find_label_line(lines, must_contain=["énom", "non"])
    last_name_label = _find_label_line(lines, must_contain=["siyati"])
    first_name = _find_value_below(lines, first_name_label) if first_name_label else None
    last_name = _find_value_below(lines, last_name_label) if last_name_label else None

    name_parts = [part for part in (first_name, last_name) if part]
    if name_parts:
        fields["full_name"] = " ".join(name_parts).upper()

    sex_label = _find_label_line(lines, must_contain=["sexe", "seks"])
    if sex_label:
        value = _find_value_below(lines, sex_label)
        if value and value.strip().upper() in ("M", "F"):
            fields["sex"] = value.strip().upper()

    place_label = _find_label_line(lines, must_contain=["lieu", "kote"])
    if place_label:
        value = _find_row_below(lines, place_label)
        if value:
            fields["place_of_birth"] = value.upper()

    # "emission"/"émission" : sans ambiguïté avec "expiration" (mots
    # distincts) — pas besoin d'exclusion supplémentaire au-delà du
    # vocabulaire d'étiquettes déjà filtré dans _value_candidates_below.
    issue_label = _find_label_line(lines, must_contain=["emission", "émission"])
    if issue_label:
        fields["date_of_issue"] = _parse_date(_find_value_below(lines, issue_label))

    expiry_label = _find_label_line(lines, must_contain=["expiration"])
    if expiry_label:
        fields["date_of_expiry"] = _parse_date(_find_value_below(lines, expiry_label))

    return fields


_OCR_FIELD_KEYS = (
    "document_number", "full_name", "date_of_birth",
    "sex", "place_of_birth", "date_of_issue", "date_of_expiry",
)


def _empty_ocr_fields() -> dict:
    return {key: None for key in _OCR_FIELD_KEYS}


def _extract_ocr_fields_via_claude(document_type: str, front_photo_path: str) -> dict | None:
    """Extraction OCR via l'API de vision Claude (Anthropic) — en test face à
    EasyOCR/_extract_cni_fields (ancrage par étiquette/position, fragile face
    aux erreurs de lecture de l'étiquette elle-même). Retourne None (jamais
    d'exception) si la clé n'est pas configurée ou si l'appel échoue, pour un
    repli transparent sur EasyOCR — jamais de dépendance dure à un service
    tiers pour une fonctionnalité déjà existante.
    """
    import base64
    import urllib.request
    import urllib.error

    api_key = os.environ.get("ANTHROPIC_API_KEY", "").strip()
    if not api_key:
        return None

    model = os.environ.get("ANTHROPIC_OCR_MODEL", "claude-sonnet-5").strip() or "claude-sonnet-5"

    try:
        with open(front_photo_path, "rb") as f:
            image_b64 = base64.b64encode(f.read()).decode("ascii")
    except OSError as exc:
        log(f"Claude OCR: lecture image impossible: {exc}")
        return None

    doc_label = "carte d'identification nationale haïtienne (recto)" if document_type in ("cni", "national_id") else "passeport"

    prompt = (
        f"Voici la photo d'une {doc_label}. Extrais EXACTEMENT les champs suivants tels "
        "qu'imprimés sur le document, sans les traduire ni les reformater au-delà du format "
        "demandé. Réponds UNIQUEMENT avec un objet JSON valide, aucun texte autour, aucun bloc "
        "de code — juste le JSON brut, avec exactement ces clés :\n"
        '{"document_number": "numéro d\'identification unique (NIU) — pas le numéro de carte",'
        ' "full_name": "prénom + nom en majuscules",'
        ' "date_of_birth": "YYYY-MM-DD",'
        ' "sex": "M ou F",'
        ' "place_of_birth": "lieu de naissance",'
        ' "date_of_issue": "YYYY-MM-DD",'
        ' "date_of_expiry": "YYYY-MM-DD"}\n'
        "Mets null (pas la chaîne \"null\") pour tout champ absent, illisible, ou dont tu n'es "
        "pas raisonnablement sûr — ne devine jamais une valeur plausible à la place d'une "
        "valeur réellement lue sur le document."
    )

    payload = json.dumps({
        "model": model,
        "max_tokens": 1024,
        "messages": [{
            "role": "user",
            "content": [
                {"type": "image", "source": {"type": "base64", "media_type": "image/jpeg", "data": image_b64}},
                {"type": "text", "text": prompt},
            ],
        }],
    }).encode("utf-8")

    request = urllib.request.Request(
        "https://api.anthropic.com/v1/messages",
        data=payload,
        method="POST",
        headers={
            "Content-Type": "application/json",
            "x-api-key": api_key,
            "anthropic-version": "2023-06-01",
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=45) as response:
            body = json.loads(response.read().decode("utf-8"))
    except (urllib.error.URLError, urllib.error.HTTPError, TimeoutError) as exc:
        log(f"Claude OCR: appel API échoué: {exc}")
        return None
    except Exception as exc:  # noqa: BLE001 - jamais crasher tout le script pour un appel externe
        log(f"Claude OCR: erreur inattendue: {exc}")
        return None

    try:
        text = body["content"][0]["text"].strip()
        # Au cas où le modèle encadrerait quand même sa réponse d'un bloc de
        # code malgré la consigne — tolérance, pas une hypothèse de départ.
        if text.startswith("```"):
            text = text.strip("`")
            text = text[4:] if text.lower().startswith("json") else text
        parsed = json.loads(text)
    except (KeyError, IndexError, json.JSONDecodeError) as exc:
        log(f"Claude OCR: réponse inexploitable: {exc}")
        return None

    fields = _empty_ocr_fields()
    for key in _OCR_FIELD_KEYS:
        value = parsed.get(key)
        if isinstance(value, str) and value.strip():
            fields[key] = value.strip()

    return fields


def _extract_ocr_fields_via_easyocr(document_type: str, front_photo_path: str) -> dict:
    """Retourne {"document_number": ?str, "full_name": ?str, "date_of_birth": ?str,
    "sex": ?str, "place_of_birth": ?str, "date_of_issue": ?str, "date_of_expiry": ?str}.

    Champs de date toujours normalisés en YYYY-MM-DD si trouvés. Pour un
    passeport (pas encore de vrai échantillon pour calibrer une extraction
    ancrée par position, voir _extract_cni_fields), seuls document_number/
    full_name/date_of_birth peuvent être renseignés — les 4 champs
    supplémentaires (sex, place_of_birth, date_of_issue, date_of_expiry)
    restent toujours null dans ce cas.
    """
    fields = _empty_ocr_fields()

    try:
        # EASYOCR_MODULE_PATH doit être fixée AVANT l'import : easyocr/config.py
        # résout son BASE_PATH ("~/.EasyOCR/" par défaut) au niveau module, à
        # l'import — passer model_storage_directory au constructeur Reader()
        # ne suffit pas, une autre partie de l'initialisation retombe quand
        # même sur ce chemin par défaut (confirmé : même erreur reproduite
        # avec le seul model_storage_directory).
        os.makedirs(_EASYOCR_MODEL_DIR, exist_ok=True)
        os.environ.setdefault("EASYOCR_MODULE_PATH", _EASYOCR_MODEL_DIR)
        import easyocr
    except ImportError:
        log("easyocr n'est pas installé — extraction OCR ignorée.")
        return fields

    try:
        # Français (langue administrative d'Haïti) + anglais (souvent présent
        # sur les passeports, mentions bilingues).
        reader = easyocr.Reader(["fr", "en"], gpu=False, model_storage_directory=_EASYOCR_MODEL_DIR)
        raw_results = reader.readtext(_load_image_for_ocr(front_photo_path), detail=1)
    except Exception as exc:  # noqa: BLE001 - on ne veut jamais crasher tout le script pour l'OCR seul
        log(f"echec OCR: {exc}")
        return fields

    # "cni" : valeur historique du flux citoyen (Document/AnalyzeDocumentJob).
    # "national_id" : valeur envoyée par le flux session partenaire (voir
    # CardIdType::NationalId côté SwapLajan) — deux noms pour le même type de
    # pièce, jamais unifiés jusqu'ici. Sans les deux, une session partenaire
    # carte nationale retombait silencieusement sur l'heuristique passeport
    # ci-dessous (jamais calibrée pour la mise en page à deux colonnes de la
    # CIN), et n'extrayait rien — confirmé sur deux sessions de test réelles.
    if document_type in ("cni", "national_id"):
        return _extract_cni_fields(_ocr_line_boxes(raw_results))

    # Passeport : pas encore de vrai échantillon pour calibrer une extraction
    # ancrée par position (voir _extract_cni_fields) — heuristique large sur
    # le texte brut en attendant, comme avant.
    lines = [text.strip() for (_bbox, text, _confidence) in raw_results if text.strip()]
    full_text = " ".join(lines)

    match = re.search(r"\b[A-Z0-9]{6,20}\b", full_text.upper())
    if match:
        fields["document_number"] = match.group(0)

    date_match = re.search(r"\b(\d{2})[./-](\d{2})[./-](\d{4})\b", full_text)
    if date_match:
        day, month, year = date_match.groups()
        fields["date_of_birth"] = f"{year}-{month}-{day}"

    name_candidates = [
        line for line in lines
        if re.fullmatch(r"[A-ZÀ-Ÿ' -]{4,}", line.upper()) and not re.search(r"\d", line)
    ]
    if name_candidates:
        fields["full_name"] = max(name_candidates, key=len).upper()

    return fields


def extract_ocr_fields(document_type: str, front_photo_path: str) -> dict:
    """Point d'entrée OCR — essaie l'API de vision Claude en premier si
    ANTHROPIC_API_KEY est configurée (voir _extract_ocr_fields_via_claude),
    repli automatique et transparent sur EasyOCR
    (_extract_ocr_fields_via_easyocr) si la clé est absente ou si l'appel
    échoue pour quelque raison que ce soit — jamais de dépendance dure à un
    service tiers pour une fonctionnalité qui marchait déjà en local.
    """
    claude_fields = _extract_ocr_fields_via_claude(document_type, front_photo_path)
    if claude_fields is not None:
        return claude_fields

    return _extract_ocr_fields_via_easyocr(document_type, front_photo_path)


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
            if not liveness_passed:
                # Verdict métier légitime (pas une panne technique) — quand
                # même noté pour que le résultat final reste explicable :
                # sans ça, un liveness_passed=False silencieux ressemble à un
                # bug plutôt qu'à un vrai refus du modèle anti-spoofing.
                warnings.append("passive_liveness_failed")
        else:
            warnings.append("no_face_detected_in_selfie")
    except ValueError as exc:
        # DeepFace lève parfois une ValueError explicite "Spoof detected" selon
        # la version — dans ce cas précis, on sait que c'est bien un échec de
        # vivacité, pas une panne technique.
        if "spoof" in str(exc).lower():
            liveness_passed = False
            warnings.append("passive_liveness_failed")
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

# Indices de landmarks mediapipe FaceLandmarker (topologie 468 points) : bord
# du visage côté gauche/droite de l'image et pointe du nez.
_MEDIAPIPE_LEFT_FACE_EDGE = 234
_MEDIAPIPE_RIGHT_FACE_EDGE = 454
_MEDIAPIPE_NOSE_TIP = 1

# Modèles .task auto-hébergés (voir scripts/face-verification/models/,
# commités dans le repo) — l'API "legacy" mediapipe.solutions.* n'existe plus
# dans les wheels mediapipe récentes (confirmé en conditions réelles : même
# avec mediapipe épinglé <1, le paquet installé n'a ni mediapipe.solutions ni
# mediapipe.python, seulement mediapipe.tasks). On utilise donc directement
# l'API "Tasks", plus moderne, la seule fiable.
_SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
_FACE_LANDMARKER_MODEL_PATH = os.path.join(_SCRIPT_DIR, "models", "face_landmarker.task")
_HAND_LANDMARKER_MODEL_PATH = os.path.join(_SCRIPT_DIR, "models", "hand_landmarker.task")

# Par défaut, EasyOCR télécharge/lit ses modèles dans ~/.EasyOCR — sur le
# worker de production (utilisateur www-data), ce home n'est ni inscriptible
# ni lisible, faisant échouer Reader() avec une PermissionError silencieuse
# (avalée par le except Exception plus bas, jamais remontée) — confirmé sur
# deux sessions de test réelles où l'OCR revenait entièrement vide sans
# erreur visible. Même principe que DEEPFACE_HOME pour DeepFace : un cache
# dédié dans le projet, hors du home utilisateur.
_EASYOCR_MODEL_DIR = os.path.join(_SCRIPT_DIR, ".easyocr-cache")


def _load_mp_image(image_path: str):
    import cv2
    import mediapipe as mp

    image = cv2.imread(image_path)
    if image is None:
        return None

    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    return mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_image)


def _create_face_landmarker():
    import mediapipe as mp
    from mediapipe.tasks.python import BaseOptions
    from mediapipe.tasks.python.vision import FaceLandmarker, FaceLandmarkerOptions, RunningMode

    options = FaceLandmarkerOptions(
        base_options=BaseOptions(model_asset_path=_FACE_LANDMARKER_MODEL_PATH),
        running_mode=RunningMode.IMAGE,
        num_faces=1,
        min_face_detection_confidence=0.5,
    )
    return FaceLandmarker.create_from_options(options)


def _create_hand_landmarker():
    from mediapipe.tasks.python import BaseOptions
    from mediapipe.tasks.python.vision import HandLandmarker, HandLandmarkerOptions, RunningMode

    options = HandLandmarkerOptions(
        base_options=BaseOptions(model_asset_path=_HAND_LANDMARKER_MODEL_PATH),
        running_mode=RunningMode.IMAGE,
        num_hands=2,
        min_hand_detection_confidence=0.5,
    )
    return HandLandmarker.create_from_options(options)


def _detect_face_landmarks(face_landmarker, image_path: str):
    """Landmarks du premier visage détecté (liste d'objets avec .x/.y/.z), ou
    None si aucun visage. Réutilise un FaceLandmarker déjà créé — le créer une
    fois par lot d'images, pas une fois par image (coût de chargement du
    modèle).
    """
    mp_image = _load_mp_image(image_path)
    if mp_image is None:
        return None

    result = face_landmarker.detect(mp_image)
    if not result.face_landmarks:
        return None

    return result.face_landmarks[0]


def _horizontal_nose_offset(landmarks) -> float:
    """Calcule la position du nez par rapport au centre du visage, normalisée
    par la largeur du visage : proche de 0 quand la personne fait face à la
    caméra, s'éloigne de 0 (signe selon le sens) quand la tête est tournée.
    """
    left_x = landmarks[_MEDIAPIPE_LEFT_FACE_EDGE].x
    right_x = landmarks[_MEDIAPIPE_RIGHT_FACE_EDGE].x
    nose_x = landmarks[_MEDIAPIPE_NOSE_TIP].x

    face_width = right_x - left_x
    if face_width == 0:
        return 0.0

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

    try:
        with _create_face_landmarker() as face_landmarker:
            for label, path in (("left", selfie_left_path), ("center", selfie_center_path), ("right", selfie_right_path)):
                landmarks = _detect_face_landmarks(face_landmarker, path)

                if landmarks is None:
                    warnings.append(f"active_liveness_no_face_in_{label}_frame")
                    return None, warnings

                offsets[label] = _horizontal_nose_offset(landmarks)
    except Exception as exc:  # noqa: BLE001
        warnings.append(f"active_liveness_error: {exc}")
        return None, warnings

    center_ok = abs(offsets["center"]) < ACTIVE_LIVENESS_CENTER_THRESHOLD
    left_turned = abs(offsets["left"]) > ACTIVE_LIVENESS_TURN_THRESHOLD
    right_turned = abs(offsets["right"]) > ACTIVE_LIVENESS_TURN_THRESHOLD
    opposite_sides = (offsets["left"] > 0) != (offsets["right"] > 0)

    active_liveness_passed = center_ok and left_turned and right_turned and opposite_sides

    if not active_liveness_passed:
        warnings.append("active_liveness_no_head_turn_detected")

    return active_liveness_passed, warnings


def _face_bounding_box(landmarks):
    """(min_x, min_y, max_x, max_y) normalisé 0-1 à partir de landmarks déjà
    détectés (voir _detect_face_landmarks)."""
    xs = [lm.x for lm in landmarks]
    ys = [lm.y for lm in landmarks]

    return min(xs), min(ys), max(xs), max(ys)


def _detect_hand_landmarks(hand_landmarker, image_path: str) -> list:
    """Une liste de jeux de landmarks (un par main détectée), vide si aucune.
    Réutilise un HandLandmarker déjà créé, même principe que
    _detect_face_landmarks."""
    mp_image = _load_mp_image(image_path)
    if mp_image is None:
        return []

    result = hand_landmarker.detect(mp_image)
    return result.hand_landmarks or []


# Indices des 5 bouts de doigts (topologie main à 21 points) — seuls ces
# points comptent, pas les 21 (poignet/base de paume inclus). Confirmé en
# test manuel réel : compter tous les points de la main faisait remonter un
# faux positif quand la main tenant le téléphone passait près de la mâchoire
# pendant une rotation de tête, sans jamais couvrir le visage — le poignet/la
# paume qui tient l'appareil est naturellement proche du bord du cadre, alors
# que de vrais doigts posés sur la bouche/le menton se distinguent par
# plusieurs BOUTS de doigts (pas juste la paume) qui empiètent sur le centre
# du visage.
_HAND_FINGERTIP_INDICES = (4, 8, 12, 16, 20)

# Une main est considérée comme "sur le visage" seulement si au moins 2 des 5
# bouts de doigts tombent dans la zone centrale du visage (pas un seul doigt
# isolé qui frôle le bord).
HAND_OCCLUSION_MIN_FINGERTIPS_INSIDE = 2

# Rétrécit fortement la boîte du visage testée, vers sa zone centrale
# (yeux/nez/bouche) — exclut la mâchoire/les oreilles/le contour, là où une
# main tenant le téléphone se trouve naturellement sans occlusion réelle.
HAND_OCCLUSION_BOX_MARGIN_RATIO = 0.22


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

    try:
        with _create_face_landmarker() as face_landmarker, _create_hand_landmarker() as hand_landmarker:
            for label, path in labeled_paths:
                landmarks = _detect_face_landmarks(face_landmarker, path)
                if landmarks is None:
                    continue  # absence de visage déjà signalée ailleurs

                min_x, min_y, max_x, max_y = _face_bounding_box(landmarks)
                margin_x = (max_x - min_x) * HAND_OCCLUSION_BOX_MARGIN_RATIO
                margin_y = (max_y - min_y) * HAND_OCCLUSION_BOX_MARGIN_RATIO
                min_x, max_x = min_x + margin_x, max_x - margin_x
                min_y, max_y = min_y + margin_y, max_y - margin_y

                for hand_landmarks in _detect_hand_landmarks(hand_landmarker, path):
                    fingertips_inside = sum(
                        1 for idx in _HAND_FINGERTIP_INDICES
                        if min_x <= hand_landmarks[idx].x <= max_x and min_y <= hand_landmarks[idx].y <= max_y
                    )
                    if fingertips_inside >= HAND_OCCLUSION_MIN_FINGERTIPS_INSIDE:
                        occlusion_detected = True
                        warnings.append(f"hand_occlusion_detected_in_{label}_frame")
                        break
    except Exception as exc:  # noqa: BLE001 - jamais crasher tout le script pour ce contrôle seul
        warnings.append(f"hand_occlusion_check_error: {exc}")

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

    if occlusion_detected:
        # Priorité absolue : une main détectée sur le visage rejette la
        # vivacité quoi qu'il arrive, même si un autre contrôle échoue par
        # ailleurs pour une raison technique (ex. la main elle-même gêne la
        # détection du visage sur un autre frame, laissant active_passed à
        # None) — un résultat "inconclusif" ne doit jamais masquer un rejet
        # explicite déjà constaté.
        liveness_passed = False
    elif passive_passed is None or active_passed is None:
        liveness_passed = None
    else:
        liveness_passed = bool(passive_passed and active_passed)

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
