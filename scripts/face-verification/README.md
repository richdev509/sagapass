# face-verification

Moteur d'extraction OCR + détection de vivacité + correspondance faciale, invoqué
depuis Laravel via `App\Services\FaceVerification\FaceVerificationScriptClient`
(voir `config/faceverification.php`).

Isolé du reste du projet (même principe que d'autres intégrations "script externe"
comparables : dépendances lourdes, jamais mêlées au reste de l'application).

## Installation (jamais fait sur ce serveur avant — Python n'y existe pas encore)

```bash
apt install -y python3 python3-venv python3-pip
apt install -y libgl1 libglib2.0-0   # dépendances système d'OpenCV/EasyOCR

cd scripts/face-verification
python3 -m venv venv
venv/bin/pip install -r requirements.txt

mkdir -p .deepface-cache
chown -R www-data:www-data .deepface-cache
```

`FaceVerificationScriptClient` appelle `python3` directement (pas `venv/bin/python3`)
— sur le serveur, soit activer le venv avant que le worker de queue démarre
(wrapper dans le fichier Supervisor), soit ajuster l'appel pour utiliser
`venv/bin/python3` explicitement. À trancher au moment du déploiement réel.

## Test manuel avant de brancher le job

```bash
venv/bin/python3 analyze.py cni /chemin/vers/recto.jpg /chemin/vers/verso.jpg /chemin/vers/selfie.jpg
```

Doit afficher une seule ligne JSON sur stdout (voir le docstring de `analyze.py`).

## Calibration OCR — pas encore faite

Les heuristiques d'extraction de champs (`extract_ocr_fields()` dans `analyze.py`)
sont des points de départ, pas calibrées sur de vrais échantillons de CIN/passeport
haïtiens. À ajuster une fois des exemples réels disponibles.

## Empreinte faciale (détection de doublons)

En plus de l'OCR, de la vivacité et du score de correspondance, `analyze.py`
renvoie `face_embedding` : l'empreinte SFace (128 valeurs) du selfie central,
produite par `DeepFace.represent` (même modèle que la correspondance, aucune
dépendance en plus). Laravel la compare à toutes les empreintes déjà vérifiées
(`FaceDuplicateService`) — le script ne fait que la produire. Valeur `null` si
l'extraction échoue (un avertissement `face_embedding_*` est alors ajouté) : la
session continue, le contrôle de doublons est simplement marqué « unchecked ».
Donnée biométrique : Laravel ne la stocke jamais en clair.
