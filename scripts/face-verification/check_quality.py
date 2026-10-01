"""Contrôle de qualité d'un selfie AVANT la vérification complète.

  python check_quality.py <photo> [<photo> ...]

Léger volontairement (OpenCV + YuNet seulement, ni DeepFace ni TensorFlow) : il
tourne de façon synchrone pendant la requête de capture, pour que le client
puisse refaire la photo tout de suite si elle est inexploitable.

Sortie JSON (une seule ligne) :
  {"images": [{"path": "...", "ok": bool, "reasons": [...], "brightness": ...,
               "contrast": ..., "sharpness": ..., "face_size": ...}, ...]}
Motifs possibles : no_face, unreadable_image, face_too_small, face_too_dark,
face_low_contrast, face_blurry, multiple_faces (voir opencv_face.py).
Ne lève jamais : une erreur interne sort en {"images": [], "error": "..."} avec
code 0, et l'appelant (Laravel) laisse alors passer la capture.
"""
from __future__ import annotations

import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def main() -> int:
    try:
        import opencv_face

        images = []
        for path in sys.argv[1:]:
            result = opencv_face.embed_path(path)
            reasons = list(result.reject_reasons)
            # Aucun visage / image illisible : embed_* le signale en warning.
            for w in result.warnings:
                if w in ("no_face", "unreadable_image") and w not in reasons:
                    reasons.append(w)
            images.append({
                "path": path,
                "ok": not reasons and result.embedding is not None,
                "reasons": reasons,
                "brightness": result.brightness,
                "contrast": result.contrast,
                "sharpness": result.sharpness,
                "face_size": result.face_size,
            })
        print(json.dumps({"images": images}))
    except Exception as exc:  # noqa: BLE001
        print(json.dumps({"images": [], "error": str(exc)}))
    return 0


if __name__ == "__main__":
    sys.exit(main())
