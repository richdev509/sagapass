#!/usr/bin/env python3
"""Moteur d'analyse OCR + vivacité + correspondance faciale d'un document
d'identité, invoqué depuis Laravel via FaceVerificationScriptClient (voir
app/Services/FaceVerification/).

Contrat d'E/S (voir config/faceverification.php et le plan associé) :
  argv : <document_type: cni|passport> <front_photo_path> <back_photo_path|""> <selfie_path>
  stdout : UNE seule ligne JSON (voir build_output() plus bas)
  stderr : logs uniquement
  exit 0 : l'analyse a pu tourner (même si le verdict métier est négatif)
  exit != 0 : échec technique réel (dépendance manquante, image illisible...)

IMPORTANT — non testé en conditions réelles à l'écriture de ce script : les
heuristiques d'extraction OCR par champ (regex ci-dessous) sont des points de
départ raisonnables, PAS calibrées sur de vrais échantillons de CIN/passeport
haïtiens (voir le plan : échantillons demandés à l'utilisateur, pas encore
reçus). À ajuster une fois de vrais exemples disponibles.
"""

import sys
import json
import re
import traceback


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


def analyze_face(front_photo_path: str, selfie_path: str) -> tuple:
    """Retourne (face_match_score: ?float, liveness_passed: ?bool, warnings: list[str])."""
    warnings: list[str] = []
    face_match_score = None
    liveness_passed = None

    try:
        from deepface import DeepFace
    except ImportError:
        log("deepface n'est pas installé — correspondance/vivacité ignorées.")
        warnings.append("deepface_unavailable")
        return face_match_score, liveness_passed, warnings

    # Vivacité : DeepFace embarque un modèle anti-spoofing (MiniFASNet) depuis
    # 0.0.90+, exposé via extract_faces(..., anti_spoofing=True). On l'appelle
    # séparément de verify() pour ne jamais laisser une exception de spoofing
    # interrompre le calcul de la correspondance visage ci-dessous.
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

    # Correspondance visage pièce <-> selfie (sans anti_spoofing ici, déjà géré
    # ci-dessus séparément). model_name="SFace" : modèle volontairement léger
    # (quelques Mo, backend ONNX) plutôt que le VGG-Face par défaut de DeepFace
    # (~580 Mo de poids + TensorFlow complet) — le serveur de déploiement est
    # une VM partagée à RAM très limitée (~3.8 Go, plusieurs autres apps), où
    # VGG-Face a provoqué un OOM-kill du process en conditions réelles.
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

    return face_match_score, liveness_passed, warnings


def main() -> int:
    if len(sys.argv) != 5:
        log("Usage: analyze.py <document_type> <front_photo_path> <back_photo_path|\"\"> <selfie_path>")
        return 1

    document_type, front_photo_path, _back_photo_path, selfie_path = sys.argv[1:5]

    try:
        ocr_fields = extract_ocr_fields(document_type, front_photo_path)
    except Exception:  # noqa: BLE001 - jamais crasher sur l'OCR seul
        log(traceback.format_exc())
        ocr_fields = {"document_number": None, "full_name": None, "date_of_birth": None}

    face_match_score, liveness_passed, warnings = analyze_face(front_photo_path, selfie_path)

    output = {
        "ocr": ocr_fields,
        "liveness_passed": liveness_passed,
        "face_match_score": face_match_score,
        "warnings": warnings,
    }

    print(json.dumps(output))
    return 0


if __name__ == "__main__":
    sys.exit(main())
