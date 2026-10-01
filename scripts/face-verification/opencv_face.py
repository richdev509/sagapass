"""Pipeline visage OpenCV natif : YuNet (détection + 5 repères) + SFace.

DÉVELOPPÉ EN PARALLÈLE de analyze.py, pas branché nulle part pour l'instant.
Même pipeline que le projet Security_system (face_engine.py) : c'est celui pour
lequel le seuil cosinus 0.363 de SFace a été mesuré (OpenCV Zoo). analyze.py,
lui, passe par DeepFace sans detector_backend, donc détecteur Haar et
alignement grossier, avec pourtant le même seuil.

Ne dépend PAS de deepface ni de TensorFlow (opencv-contrib seul).
Ne lève jamais vers l'appelant : voir OpenCvFaceResult.
"""
from __future__ import annotations

import os
from dataclasses import dataclass, field

import cv2
import numpy as np

MODELS_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "models")
DETECTOR_MODEL = os.path.join(MODELS_DIR, "face_detection_yunet_2023mar.onnx")
RECOGNIZER_MODEL = os.path.join(MODELS_DIR, "face_recognition_sface_2021dec.onnx")

# Seuil officiel OpenCV Zoo pour SFace (similarité cosinus), valable pour CE pipeline.
COSINE_THRESHOLD = 0.363
DETECTION_CONFIDENCE = 0.8
MIN_FACE_SIZE = 80          # pixels, côté le plus court du visage détecté
MAX_SIDE = 1280             # réduit les très grandes photos avant détection
PAD_RETRY = 0.5             # bordure ajoutée au 2e essai de détection (fraction du grand côté)

# Contrôle de qualité du visage (mesuré sur les zones de visage redimensionnées
# à 160x160). Seuils choisis sur 27 selfies réels de prod (2026-09-30) : à
# recalibrer quand il y aura plus de données.
MIN_FACE_BRIGHTNESS = 75    # luminosité moyenne 0-255 ; en dessous : trop sombre
MIN_FACE_CONTRAST = 20      # écart-type des niveaux de gris ; en dessous : délavé/sans relief
MIN_FACE_SHARPNESS = 100    # variance du Laplacien ; en dessous : flou


@dataclass
class OpenCvFaceResult:
    embedding: np.ndarray | None = None        # 128 valeurs, normalisées L2
    face_size: float | None = None              # côté le plus court, en pixels
    detection_score: float | None = None
    faces_found: int = 0
    brightness: float | None = None
    contrast: float | None = None
    sharpness: float | None = None
    # Motifs de rejet : photo à refaire (aucune empreinte fiable à en tirer).
    # Vide = photo utilisable. Valeurs : face_too_small, face_too_dark,
    # face_low_contrast, face_blurry, multiple_faces.
    reject_reasons: list[str] = field(default_factory=list)
    warnings: list[str] = field(default_factory=list)


_detector = None
_recognizer = None


def _load_models() -> None:
    global _detector, _recognizer
    if _detector is None:
        _detector = cv2.FaceDetectorYN.create(
            DETECTOR_MODEL, "", (320, 320), score_threshold=DETECTION_CONFIDENCE
        )
    if _recognizer is None:
        _recognizer = cv2.FaceRecognizerSF.create(RECOGNIZER_MODEL, "")


def read_image(path: str) -> np.ndarray | None:
    """Lecture tolérante aux chemins non ASCII (Windows) ; l'orientation EXIF des
    photos de téléphone est appliquée par imdecode."""
    try:
        data = np.fromfile(path, dtype=np.uint8)
        return cv2.imdecode(data, cv2.IMREAD_COLOR)
    except Exception:  # noqa: BLE001
        return None


def embed_image(image_bgr: np.ndarray) -> OpenCvFaceResult:
    result = OpenCvFaceResult()
    try:
        _load_models()
        h, w = image_bgr.shape[:2]
        scale = 1.0
        if max(h, w) > MAX_SIDE:
            scale = MAX_SIDE / max(h, w)
            image_bgr = cv2.resize(image_bgr, (int(w * scale), int(h * scale)))
            h, w = image_bgr.shape[:2]

        _detector.setInputSize((w, h))
        _, faces = _detector.detect(image_bgr)
        if faces is None or len(faces) == 0:
            # Selfies très serrés (visage qui déborde du cadre) : YuNet ne les
            # voit pas. Second essai avec 50 % de bordure grise autour
            # (mesuré : rattrape 7 selfies sur 7 en local, sans baisser le
            # seuil de confiance).
            pad = int(max(h, w) * PAD_RETRY)
            image_bgr = cv2.copyMakeBorder(
                image_bgr, pad, pad, pad, pad, cv2.BORDER_CONSTANT, value=(128, 128, 128)
            )
            h, w = image_bgr.shape[:2]
            if max(h, w) > MAX_SIDE:
                # YuNet retrouve mieux un très gros visage sur une image réduite.
                s2 = MAX_SIDE / max(h, w)
                image_bgr = cv2.resize(image_bgr, (int(w * s2), int(h * s2)))
                scale *= s2
                h, w = image_bgr.shape[:2]
            _detector.setInputSize((w, h))
            _, faces = _detector.detect(image_bgr)
            if faces is not None and len(faces) > 0:
                result.warnings.append("face_found_after_padding")
        if faces is None or len(faces) == 0:
            result.warnings.append("no_face")
            return result

        result.faces_found = len(faces)
        areas = faces[:, 2] * faces[:, 3]
        order = np.argsort(areas)[::-1]
        face = faces[order[0]]
        if len(faces) > 1 and areas[order[1]] > 0.5 * areas[order[0]]:
            result.warnings.append("multiple_faces")
            result.reject_reasons.append("multiple_faces")

        size = float(min(face[2], face[3]))
        result.face_size = round(size / scale, 1)
        result.detection_score = round(float(face[-1]), 3)
        if size / scale < MIN_FACE_SIZE:
            result.warnings.append("face_too_small")
            result.reject_reasons.append("face_too_small")

        x, y, fw, fh = [max(int(v), 0) for v in face[:4]]
        roi = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2GRAY)[y:y + fh, x:x + fw]
        if roi.size:
            roi = cv2.resize(roi, (160, 160))
            result.brightness = round(float(roi.mean()), 1)
            result.contrast = round(float(roi.std()), 1)
            result.sharpness = round(float(cv2.Laplacian(roi, cv2.CV_64F).var()), 1)
            if result.brightness < MIN_FACE_BRIGHTNESS:
                result.reject_reasons.append("face_too_dark")
            if result.contrast < MIN_FACE_CONTRAST:
                result.reject_reasons.append("face_low_contrast")
            if result.sharpness < MIN_FACE_SHARPNESS:
                result.reject_reasons.append("face_blurry")

        aligned = _recognizer.alignCrop(image_bgr, face)
        feat = _recognizer.feature(aligned).flatten().astype(np.float32)
        norm = np.linalg.norm(feat)
        result.embedding = feat / norm if norm > 0 else feat
    except Exception as exc:  # noqa: BLE001
        result.warnings.append(f"opencv_face_error: {exc}")
    return result


def embed_path(path: str) -> OpenCvFaceResult:
    image = read_image(path)
    if image is None:
        return OpenCvFaceResult(warnings=["unreadable_image"])
    return embed_image(image)


def cosine_similarity(a: np.ndarray, b: np.ndarray) -> float:
    na, nb = np.linalg.norm(a), np.linalg.norm(b)
    return float(np.dot(a, b) / (na * nb)) if na > 0 and nb > 0 else 0.0
