<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Vérification d'identité - Kaypa</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .phone-frame {
      width: 100%;
      max-width: 390px;
      height: 844px;
      background: #f8f9fa;
      border-radius: 50px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      overflow: hidden;
      position: relative;
    }

    .status-bar {
      height: 44px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      font-size: 14px;
      font-weight: 600;
    }

    .progress-container {
      padding: 20px;
      position: relative;
    }

    .progress-steps {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .progress-step {
      width: 30%;
      height: 4px;
      background: #e0e0e0;
      border-radius: 10px;
      overflow: hidden;
      position: relative;
    }

    .progress-step.active .progress-fill {
      width: 100%;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      width: 0;
      transition: width 0.5s ease;
    }

    .progress-label {
      text-align: center;
      font-size: 12px;
      color: #667eea;
      font-weight: 600;
      margin-top: 5px;
    }

    .content {
      padding: 0 30px;
      height: calc(100% - 150px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .step {
      display: none;
      width: 100%;
      text-align: center;
      animation: fadeInUp 0.5s ease;
    }

    .step.active {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .illustration {
      width: 200px;
      height: 200px;
      margin-bottom: 30px;
      position: relative;
    }

    /* ID Card Illustration */
    .id-card {
      width: 180px;
      height: 120px;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      transform-style: preserve-3d;
      transition: transform 0.8s;
    }

    .id-card.flipped {
      transform: translate(-50%, -50%) rotateY(180deg);
    }

    .card-face {
      position: absolute;
      width: 100%;
      height: 100%;
      backface-visibility: hidden;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .card-front {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card-back {
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
      transform: rotateY(180deg);
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .card-photo {
      width: 40px;
      height: 50px;
      background: white;
      border-radius: 4px;
      flex-shrink: 0;
    }

    .card-info {
      flex: 1;
      text-align: left;
    }

    .card-line {
      height: 3px;
      background: white;
      margin: 5px 0;
      border-radius: 2px;
      opacity: 0.8;
    }

    .card-line:nth-child(1) { width: 80%; }
    .card-line:nth-child(2) { width: 60%; }
    .card-line:nth-child(3) { width: 90%; }

    .card-stripe {
      height: 30px;
      background: rgba(255,255,255,0.3);
      margin: 8px 0;
    }

    .card-chip {
      width: 100%;
      height: 4px;
      background: rgba(255,255,255,0.5);
      margin: 4px 0;
      border-radius: 2px;
    }

    /* Selfie Face Detection */
    .face-frame {
      width: 200px;
      height: 260px;
      position: relative;
      margin-bottom: 30px;
    }

    .face-overlay {
      position: absolute;
      width: 100%;
      height: 100%;
      border: 3px solid #667eea;
      border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% {
        border-color: #667eea;
        transform: scale(1);
      }
      50% {
        border-color: #764ba2;
        transform: scale(1.02);
      }
    }

    .face-dots {
      position: absolute;
      width: 100%;
      height: 100%;
    }

    .face-dot {
      position: absolute;
      width: 8px;
      height: 8px;
      background: #667eea;
      border-radius: 50%;
      animation: dotPulse 1.5s infinite;
    }

    @keyframes dotPulse {
      0%, 100% { opacity: 0.3; }
      50% { opacity: 1; }
    }

    .face-dot:nth-child(1) { top: 30%; left: 35%; animation-delay: 0s; }
    .face-dot:nth-child(2) { top: 30%; right: 35%; animation-delay: 0.1s; }
    .face-dot:nth-child(3) { top: 45%; left: 50%; transform: translateX(-50%); animation-delay: 0.2s; }
    .face-dot:nth-child(4) { top: 60%; left: 40%; animation-delay: 0.3s; }
    .face-dot:nth-child(5) { top: 60%; right: 40%; animation-delay: 0.4s; }
    .face-dot:nth-child(6) { top: 70%; left: 50%; transform: translateX(-50%); animation-delay: 0.5s; }

    .face-icon {
      width: 100px;
      height: 100px;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0.3;
    }

    .face-icon svg {
      width: 100%;
      height: 100%;
      fill: #667eea;
    }

    .detection-progress {
      margin-top: 20px;
      width: 200px;
      height: 6px;
      background: #e0e0e0;
      border-radius: 10px;
      overflow: hidden;
    }

    .detection-fill {
      height: 100%;
      background: linear-gradient(90deg, #4ade80, #22c55e);
      width: 0%;
      transition: width 0.3s ease;
      animation: detectProgress 3s ease-in-out infinite;
    }

    @keyframes detectProgress {
      0% { width: 0%; }
      50% { width: 75%; }
      100% { width: 100%; }
    }

    .detection-status {
      font-size: 14px;
      color: #22c55e;
      font-weight: 600;
      margin-top: 10px;
    }

    h1 {
      font-size: 28px;
      color: #1f2937;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .description {
      font-size: 15px;
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 40px;
      max-width: 300px;
    }

    .tips {
      background: #f3f4f6;
      border-radius: 16px;
      padding: 20px;
      margin-top: 20px;
      text-align: left;
    }

    .tip-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 15px;
    }

    .tip-item:last-child {
      margin-bottom: 0;
    }

    .tip-number {
      width: 24px;
      height: 24px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .tip-text {
      font-size: 14px;
      color: #4b5563;
      line-height: 1.5;
    }

    .tip-title {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 2px;
    }

    .action-button {
      width: 100%;
      padding: 18px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      border-radius: 16px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: auto;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .action-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    }

    .action-button:active {
      transform: translateY(0);
    }

    .security-badge {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #9ca3af;
    }

    .security-icon {
      width: 16px;
      height: 16px;
      fill: #667eea;
    }

    .brand {
      font-weight: 700;
      color: #667eea;
    }

    /* Document Icons */
    .doc-icon {
      width: 120px;
      height: 120px;
      position: relative;
      margin: 0 auto 30px;
    }

    .doc-shape {
      width: 100%;
      height: 100%;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
      position: relative;
      overflow: hidden;
    }

    .doc-header {
      height: 35%;
      background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .doc-body {
      padding: 12px;
    }

    .doc-line {
      height: 4px;
      background: #e5e7eb;
      margin: 6px 0;
      border-radius: 2px;
    }

    .doc-line:nth-child(1) { width: 70%; }
    .doc-line:nth-child(2) { width: 90%; }
    .doc-line:nth-child(3) { width: 60%; }

    .floating-anim {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .success-checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4ade80, #22c55e);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 30px;
      animation: scaleIn 0.5s ease;
    }

    @keyframes scaleIn {
      from {
        transform: scale(0);
        opacity: 0;
      }
      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    .checkmark {
      width: 40px;
      height: 40px;
      stroke: white;
      stroke-width: 4;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* Camera Styles */
    .camera-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #000;
      z-index: 100;
    }

    #video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .camera-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    .camera-frame {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 85%;
      height: 50%;
      border: 3px solid #fff;
      border-radius: 16px;
      box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
    }

    .camera-frame.selfie-frame {
      width: 70%;
      height: 60%;
      border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
    }

    .camera-guide {
      position: absolute;
      top: 20%;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 12px 24px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      backdrop-filter: blur(10px);
    }

    .camera-controls {
      position: absolute;
      bottom: 40px;
      left: 0;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 60px;
      pointer-events: all;
    }

    .camera-btn {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      transition: transform 0.2s;
    }

    .camera-btn:active {
      transform: scale(0.9);
    }

    .cancel-btn {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      backdrop-filter: blur(10px);
    }

    .capture-btn {
      width: 80px;
      height: 80px;
      background: white;
      position: relative;
    }

    .capture-ring {
      display: block;
      width: 70px;
      height: 70px;
      border: 4px solid #667eea;
      border-radius: 50%;
    }

    .flip-btn {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      backdrop-filter: blur(10px);
    }

    .flip-btn svg {
      width: 24px;
      height: 24px;
    }

    .camera-mode-toggle {
      position: absolute;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      display: none;
      gap: 10px;
      background: rgba(0, 0, 0, 0.5);
      padding: 6px;
      border-radius: 30px;
      backdrop-filter: blur(10px);
    }

    .camera-mode-toggle.show {
      display: flex;
    }

    .mode-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 10px 18px;
      border: none;
      background: transparent;
      color: rgba(255, 255, 255, 0.7);
      border-radius: 25px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .mode-btn.active {
      background: rgba(255, 255, 255, 0.9);
      color: #667eea;
    }

    .mode-btn svg {
      width: 18px;
      height: 18px;
    }

    /* Face Detection Overlay */
    .face-detection-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 70%;
      height: 60%;
      display: none;
      pointer-events: none;
    }

    .face-detection-overlay.active {
      display: block;
    }

    .face-outline {
      width: 100%;
      height: 100%;
      border: 4px solid #4ade80;
      border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
      position: relative;
      animation: faceDetectPulse 2s infinite;
    }

    .face-outline.detecting {
      border-color: #fbbf24;
    }

    .face-outline.success {
      border-color: #4ade80;
    }

    .face-outline.error {
      border-color: #ef4444;
    }

    @keyframes faceDetectPulse {
      0%, 100% {
        opacity: 0.8;
        transform: scale(1);
      }
      50% {
        opacity: 1;
        transform: scale(1.02);
      }
    }

    .face-detection-points {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
    }

    .detection-point {
      position: absolute;
      width: 10px;
      height: 10px;
      background: #4ade80;
      border-radius: 50%;
      animation: pointPulse 1s infinite;
    }

    @keyframes pointPulse {
      0%, 100% {
        opacity: 0.5;
        transform: scale(1);
      }
      50% {
        opacity: 1;
        transform: scale(1.3);
      }
    }

    /* Face detection points positions */
    .detection-point:nth-child(1) { top: 35%; left: 30%; animation-delay: 0s; }
    .detection-point:nth-child(2) { top: 35%; right: 30%; animation-delay: 0.1s; }
    .detection-point:nth-child(3) { top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: 0.2s; }
    .detection-point:nth-child(4) { top: 60%; left: 35%; animation-delay: 0.3s; }
    .detection-point:nth-child(5) { top: 60%; right: 35%; animation-delay: 0.4s; }
    .detection-point:nth-child(6) { bottom: 25%; left: 50%; transform: translateX(-50%); animation-delay: 0.5s; }

    .face-instruction {
      position: absolute;
      top: 15%;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 15px 25px;
      border-radius: 25px;
      font-size: 15px;
      font-weight: 600;
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      gap: 10px;
      pointer-events: none;
      animation: instructionFade 0.3s ease;
      white-space: nowrap;
    }

    @keyframes instructionFade {
      from {
        opacity: 0;
        transform: translateX(-50%) translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
      }
    }

    .instruction-icon {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .face-progress-bar {
      position: absolute;
      bottom: 140px;
      left: 50%;
      transform: translateX(-50%);
      width: 80%;
      height: 8px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      overflow: hidden;
      backdrop-filter: blur(10px);
      display: none;
    }

    .face-progress-bar.active {
      display: block;
    }

    .face-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #4ade80, #22c55e);
      width: 0%;
      transition: width 0.5s ease;
      border-radius: 10px;
    }

    .verification-steps {
      position: absolute;
      bottom: 200px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 15px;
      pointer-events: none;
    }

    .verification-step {
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(10px);
      position: relative;
    }

    .verification-step.completed {
      background: rgba(74, 222, 128, 0.9);
    }

    .verification-step.active {
      background: rgba(251, 191, 36, 0.9);
      animation: stepPulse 1s infinite;
    }

    @keyframes stepPulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .verification-step svg {
      width: 24px;
      height: 24px;
      fill: white;
    }

    .capture-btn.disabled {
      opacity: 0.5;
      pointer-events: none;
    }

    /* Preview Styles */
    .preview-container {
      width: 100%;
      max-width: 350px;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      margin-bottom: 30px;
      position: relative;
    }

    #previewImage {
      width: 100%;
      display: block;
    }

    .preview-label {
      position: absolute;
      top: 15px;
      left: 15px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .preview-actions {
      display: flex;
      gap: 15px;
      width: 100%;
    }

    .preview-actions button {
      flex: 1;
    }

    .secondary-btn {
      background: #f3f4f6;
      color: #1f2937;
      box-shadow: none;
    }

    .secondary-btn:hover {
      background: #e5e7eb;
    }
  </style>
</head>
<body>
  <div class="phone-frame">
    <div class="status-bar">

    </div>

    <div class="progress-container">
      <div class="progress-steps">
        <div class="progress-step" id="prog1">
          <div class="progress-fill"></div>
        </div>
        <div class="progress-step" id="prog2">
          <div class="progress-fill"></div>
        </div>
        <div class="progress-step" id="prog3">
          <div class="progress-fill"></div>
        </div>
      </div>
      <div class="progress-label" id="stepLabel">Étape 1 sur 3</div>
    </div>

    <div class="content">
      <!-- Step 1: Introduction -->
      <div class="step active" id="step1">
        <div class="doc-icon floating-anim">
          <div class="doc-shape">
            <div class="doc-header"></div>
            <div class="doc-body">
              <div class="doc-line"></div>
              <div class="doc-line"></div>
              <div class="doc-line"></div>
            </div>
          </div>
        </div>
        <h1>Commençons à vous connaître</h1>
        <p class="description">Préparez votre carte d'identité ou passeport. Le processus prend environ 2 minutes.</p>
        <div class="tips">
          <div class="tip-item">
            <div class="tip-number">1</div>
            <div class="tip-text">
              <div class="tip-title">Document valide</div>
              Carte d'identité ou passeport en cours de validité
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-number">2</div>
            <div class="tip-text">
              <div class="tip-title">Bon éclairage</div>
              Assurez-vous d'être dans un endroit bien éclairé
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-number">3</div>
            <div class="tip-text">
              <div class="tip-title">Surface plane</div>
              Placez le document sur une surface contrastée
            </div>
          </div>
        </div>
        <button type="button" class="action-button" onclick="nextStep()">Choisir un document</button>
      </div>
<form id="verificationForm" action="{{ route('clients.scan.upload', $token) }}" method="POST"  enctype="multipart/form-data">
    @csrf
     <input type="hidden" name="photo_front" id="photo_front">
     <input type="hidden" name="photo_back" id="photo_back">
     <input type="hidden" name="photo_selfie" id="photo_selfie">
      <!-- Step 2: ID Front -->
      <div class="step" id="step2">
        <div class="illustration">
          <div class="id-card" id="idCard">
            <div class="card-face card-front">
              <div class="card-photo"></div>
              <div class="card-info">
                <div class="card-line"></div>
                <div class="card-line"></div>
                <div class="card-line"></div>
              </div>
            </div>
            <div class="card-face card-back">
              <div class="card-stripe"></div>
              <div class="card-chip"></div>
              <div class="card-chip"></div>
              <div class="card-chip"></div>
            </div>
          </div>
        </div>
        <h1>Scannez le recto</h1>
        <p class="description">Positionnez votre document dans le cadre. Assurez-vous que toutes les informations sont lisibles.</p>
        <button type="button" class="action-button" onclick="openCamera('front')">Prendre une photo</button>
      </div>

      <!-- Camera View -->
      <div class="step" id="cameraView">
        <div class="camera-container">
          <video id="video" autoplay playsinline></video>
          <canvas id="canvas" style="display: none;"></canvas>
          <div class="camera-overlay">
            <div class="camera-frame" id="cameraFrame"></div>
            <div class="camera-guide" id="cameraGuide">
              <span id="guideText">Positionnez le document dans le cadre</span>
            </div>

            <!-- Face Detection Overlay -->
            <div class="face-detection-overlay" id="faceDetectionOverlay">
              <div class="face-outline" id="faceOutline">
                <div class="face-detection-points">
                  <div class="detection-point"></div>
                  <div class="detection-point"></div>
                  <div class="detection-point"></div>
                  <div class="detection-point"></div>
                  <div class="detection-point"></div>
                  <div class="detection-point"></div>
                </div>
              </div>
            </div>

            <div class="face-instruction" id="faceInstruction">
              <span class="instruction-icon">👤</span>
              <span id="instructionText">Centrez votre visage</span>
            </div>

            <div class="verification-steps" id="verificationSteps">
              <div class="verification-step" id="step-center">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/>
                  <circle cx="9" cy="10" r="1"/>
                  <circle cx="15" cy="10" r="1"/>
                  <path d="M9 14h6" stroke="white" stroke-width="2" fill="none"/>
                </svg>
              </div>
              <div class="verification-step" id="step-left">
                <svg viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z" transform="rotate(270 12 12)"/>
                </svg>
              </div>
              <div class="verification-step" id="step-right">
                <svg viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z" transform="rotate(90 12 12)"/>
                </svg>
              </div>
            </div>

            <div class="face-progress-bar" id="faceProgressBar">
              <div class="face-progress-fill" id="faceProgressFill"></div>
            </div>
          </div>
          <div class="camera-controls">
            <button type="button" class="camera-btn cancel-btn" onclick="closeCamera()">
              <span>✕</span>
            </button>
            <button type="button" class="camera-btn capture-btn" onclick="capturePhoto()" id="captureBtn">
              <span class="capture-ring"></span>
            </button>
            <button type="button" class="camera-btn flip-btn" onclick="flipCamera()" id="flipBtn">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M20 5h-3.17L15 3H9L7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-8 13c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                <path d="M12 9c-1.65 0-3 1.35-3 3s1.35 3 3 3 3-1.35 3-3-1.35-3-3-3z"/>
                <path d="M15 6.5l1.5 1.5h2v-2h-2z" opacity="0.7"/>
              </svg>
            </button>
          </div>
          <div class="camera-mode-toggle" id="cameraModeToggle">
            <button type="button" class="mode-btn" id="frontCamBtn" onclick="switchCameraMode('user')">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
              <span>Frontale</span>
            </button>
            <button type="button" class="mode-btn active" id="backCamBtn" onclick="switchCameraMode('environment')">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 4h-3.17L15 2H9L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 13c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
              </svg>
              <span>Arrière</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Preview Step -->
      <div class="step" id="previewStep">
        <div class="preview-container">
          <img id="previewImage" alt="Preview">
          <div class="preview-label" id="previewLabel"></div>
        </div>
        <h1>Vérifier la photo</h1>
        <p class="description" id="previewDesc">Assurez-vous que l'image est nette et lisible.</p>
        <div class="preview-actions">
          <button type="button" class="action-button secondary-btn" onclick="retakePhoto()">Reprendre</button>
          <button type="button" class="action-button" onclick="confirmPhoto()">Confirmer</button>
        </div>
      </div>

      <!-- Step 3: ID Back -->
      <div class="step" id="step3">
        <div class="illustration">
          <div class="id-card flipped">
            <div class="card-face card-front">
              <div class="card-photo"></div>
              <div class="card-info">
                <div class="card-line"></div>
                <div class="card-line"></div>
                <div class="card-line"></div>
              </div>
            </div>
            <div class="card-face card-back">
              <div class="card-stripe"></div>
              <div class="card-chip"></div>
              <div class="card-chip"></div>
              <div class="card-chip"></div>
            </div>
          </div>
        </div>
        <h1>Scannez le verso</h1>
        <p class="description">Retournez votre document et prenez une photo claire du verso.</p>
        <button type="button" class="action-button" onclick="openCamera('back')">Prendre une photo</button>
      </div>

      <!-- Step 4: Selfie -->
      <div class="step" id="step4">
        <div class="face-frame">
          <div class="face-overlay"></div>
          <div class="face-dots">
            <div class="face-dot"></div>
            <div class="face-dot"></div>
            <div class="face-dot"></div>
            <div class="face-dot"></div>
            <div class="face-dot"></div>
            <div class="face-dot"></div>
          </div>
          <div class="face-icon">
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="9" cy="10" r="1.5" fill="currentColor"/>
              <circle cx="15" cy="10" r="1.5" fill="currentColor"/>
              <path d="M9 15 Q12 17 15 15" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
        <h1>Prenez un selfie</h1>
        <p class="description">Regardez la caméra et assurez-vous que votre visage est bien centré et éclairé.</p>
        <div class="tips">
          <div class="tip-item">
            <div class="tip-number">✓</div>
            <div class="tip-text">
              <div class="tip-title">Bon éclairage</div>
              Assurez-vous d'être dans une zone bien éclairée
            </div>
          </div>
          <div class="tip-item">
            <div class="tip-number">✓</div>
            <div class="tip-text">
              <div class="tip-title">Regardez droit</div>
              Tenez votre téléphone à hauteur des yeux
            </div>
          </div>
        </div>
        <button type="button" class="action-button" onclick="openCamera('selfie')">Ouvrir la caméra</button>
      </div>

      <!-- Step 5: Success -->
      <div class="step" id="step5">
        <div class="success-checkmark">
          <svg class="checkmark" viewBox="0 0 52 52">
            <path d="M14 27 L22 35 L38 19"/>
          </svg>
        </div>
        <h1>Vérification complète !</h1>
        <p class="description">Vos documents ont été charger. Maintenant Appuyer sur le bouton Envoyé</p>
        <button class="action-button" type="submit" >Envoyé</button>
      </div>
    </div>
  </form>
    <div class="security-badge">
      <svg class="security-icon" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2L4 6v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V6l-8-4zm0 18c-4.41-1.13-7-5.18-7-9.5V7.3l7-3.5 7 3.5v3.2c0 4.32-2.59 8.37-7 9.5z"/>
      </svg>
      <span>Sécurisé par <span class="brand">Kaypa</span></span>
    </div>
  </div>

  <script>
    let currentStep = 1;
    const totalSteps = 5;
    let stream = null;
    let currentCamera = 'environment';
    let captureType = '';
    const capturedPhotos = {
      front: null,
      back: null,
      selfie: null
    };

    // Face detection variables
    let faceDetectionActive = false;
    let detectionStage = 0; // 0: center, 1: left, 2: right
    let detectionProgress = 0;
    let detectionTimer = null;
    const detectionStages = ['center', 'left', 'right'];
    const stageInstructions = {
      center: { text: 'Regardez la caméra', icon: '👤' },
      left: { text: 'Tournez à gauche', icon: '👈' },
      right: { text: 'Tournez à droite', icon: '👉' }
    };

    function nextStep() {
      document.getElementById(`step${currentStep}`).classList.remove('active');
      currentStep++;

      if (currentStep <= totalSteps) {
        document.getElementById(`step${currentStep}`).classList.add('active');
        updateProgress();
      }
    }

    function updateProgress() {
      const progressSteps = Math.min(currentStep - 1, 3);

      for (let i = 1; i <= 3; i++) {
        const prog = document.getElementById(`prog${i}`);
        if (i <= progressSteps) {
          prog.classList.add('active');
        } else {
          prog.classList.remove('active');
        }
      }

      if (currentStep <= 4) {
        document.getElementById('stepLabel').textContent = `Étape ${currentStep - 1} sur 3`;
      } else {
        document.getElementById('stepLabel').textContent = 'Terminé';
      }
    }

    async function openCamera(type) {
      captureType = type;
      const constraints = {
        video: {
          facingMode: type === 'selfie' ? 'user' : 'environment',
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      };

      try {
        stream = await navigator.mediaDevices.getUserMedia(constraints);

        // Hide current step and show camera
        document.getElementById(`step${currentStep}`).classList.remove('active');
        document.getElementById('cameraView').classList.add('active');

        const video = document.getElementById('video');
        video.srcObject = stream;

        // Update camera frame style
        const frame = document.getElementById('cameraFrame');
        const guideText = document.getElementById('guideText');

        if (type === 'selfie') {
          frame.classList.add('selfie-frame');
          guideText.textContent = 'Centrez votre visage dans le cadre';
          document.getElementById('flipBtn').style.display = 'none';
          document.getElementById('cameraModeToggle').classList.add('show');

          // Activer la détection faciale
          startFaceDetection();
        } else {
          frame.classList.remove('selfie-frame');
          guideText.textContent = 'Positionnez le document dans le cadre';
          document.getElementById('flipBtn').style.display = 'flex';
          document.getElementById('cameraModeToggle').classList.remove('show');

          // Désactiver la détection faciale
          stopFaceDetection();
        }

        currentCamera = type === 'selfie' ? 'user' : 'environment';
      } catch (err) {
        console.error('Erreur accès caméra:', err);
        alert('Impossible d\'accéder à la caméra. Veuillez autoriser l\'accès.');
      }
    }

    function capturePhoto() {
      const video = document.getElementById('video');
      const canvas = document.getElementById('canvas');
      const context = canvas.getContext('2d');

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      context.drawImage(video, 0, 0, canvas.width, canvas.height);

      const imageData = canvas.toDataURL('image/jpeg', 0.9);
      capturedPhotos[captureType] = imageData;

      stopCamera();
      showPreview(imageData);
    }

    function stopCamera() {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
      }
      stopFaceDetection();
    }

    function closeCamera() {
      stopCamera();
      document.getElementById('cameraView').classList.remove('active');
      document.getElementById(`step${currentStep}`).classList.add('active');
    }

    // Face Detection Functions
    function startFaceDetection() {
      faceDetectionActive = true;
      detectionStage = 0;
      detectionProgress = 0;

      // Show detection UI
      document.getElementById('faceDetectionOverlay').classList.add('active');
      document.getElementById('verificationSteps').style.display = 'flex';
      document.getElementById('faceProgressBar').classList.add('active');
      document.getElementById('cameraGuide').style.display = 'none';

      // Disable capture button initially
      document.getElementById('captureBtn').classList.add('disabled');

      // Reset verification steps
      detectionStages.forEach(stage => {
        document.getElementById(`step-${stage}`).classList.remove('completed', 'active');
      });

      // Start detection simulation
      simulateFaceDetection();
    }

    function stopFaceDetection() {
      faceDetectionActive = false;
      if (detectionTimer) {
        clearTimeout(detectionTimer);
        detectionTimer = null;
      }

      // Hide detection UI
      document.getElementById('faceDetectionOverlay').classList.remove('active');
      document.getElementById('verificationSteps').style.display = 'none';
      document.getElementById('faceProgressBar').classList.remove('active');
      document.getElementById('cameraGuide').style.display = 'block';
      document.getElementById('captureBtn').classList.remove('disabled');
    }

    function simulateFaceDetection() {
      if (!faceDetectionActive) return;

      const currentStage = detectionStages[detectionStage];
      const instruction = stageInstructions[currentStage];

      // Update UI
      document.getElementById('instructionText').textContent = instruction.text;
      document.querySelector('.instruction-icon').textContent = instruction.icon;

      // Update verification steps
      document.getElementById(`step-${currentStage}`).classList.add('active');

      // Simulate detection progress
      const detectionDuration = 3000; // 3 seconds per stage
      const progressInterval = 50;
      let elapsed = 0;

      const progressTimer = setInterval(() => {
        if (!faceDetectionActive) {
          clearInterval(progressTimer);
          return;
        }

        elapsed += progressInterval;
        const stageProgress = (elapsed / detectionDuration) * 33.33;
        const totalProgress = (detectionStage * 33.33) + stageProgress;

        document.getElementById('faceProgressFill').style.width = totalProgress + '%';

        // Visual feedback with face outline
        const faceOutline = document.getElementById('faceOutline');
        if (elapsed < detectionDuration * 0.3) {
          faceOutline.className = 'face-outline detecting';
        } else if (elapsed < detectionDuration) {
          faceOutline.className = 'face-outline success';
        }

        if (elapsed >= detectionDuration) {
          clearInterval(progressTimer);
          completeDetectionStage();
        }
      }, progressInterval);
    }

    function completeDetectionStage() {
      const currentStage = detectionStages[detectionStage];

      // Mark current stage as completed
      document.getElementById(`step-${currentStage}`).classList.remove('active');
      document.getElementById(`step-${currentStage}`).classList.add('completed');

      detectionStage++;

      if (detectionStage < detectionStages.length) {
        // Continue to next stage
        detectionTimer = setTimeout(() => {
          simulateFaceDetection();
        }, 500);
      } else {
        // All stages completed
        completeAllDetection();
      }
    }

    function completeAllDetection() {
      // Update progress to 100%
      document.getElementById('faceProgressFill').style.width = '100%';

      // Show success message
      document.getElementById('instructionText').textContent = 'Vérification réussie !';
      document.querySelector('.instruction-icon').textContent = '✅';

      const faceOutline = document.getElementById('faceOutline');
      faceOutline.className = 'face-outline success';

      // Enable capture button
      setTimeout(() => {
        document.getElementById('captureBtn').classList.remove('disabled');
      }, 1000);
    }

    function showPreview(imageData) {
      document.getElementById('cameraView').classList.remove('active');
      document.getElementById('previewStep').classList.add('active');

      const previewImage = document.getElementById('previewImage');
      const previewLabel = document.getElementById('previewLabel');
      const previewDesc = document.getElementById('previewDesc');

      previewImage.src = imageData;

      const labels = {
        front: { label: 'Recto', desc: 'Vérifiez que toutes les informations sont lisibles' },
        back: { label: 'Verso', desc: 'Assurez-vous que l\'image est nette' },
        selfie: { label: 'Selfie', desc: 'Vérifiez que votre visage est bien visible' }
      };

      previewLabel.textContent = labels[captureType].label;
      previewDesc.textContent = labels[captureType].desc;
    }

    function retakePhoto() {
      document.getElementById('previewStep').classList.remove('active');
      openCamera(captureType);
    }
//debut
    function confirmPhoto() {
     document.getElementById('previewStep').classList.remove('active');

  // Sauvegarder la photo capturée dans les champs hidden
  if (captureType === 'front') {
    document.getElementById('photo_front').value = capturedPhotos.front;
  } else if (captureType === 'back') {
    document.getElementById('photo_back').value = capturedPhotos.back;
  } else if (captureType === 'selfie') {
    document.getElementById('photo_selfie').value = capturedPhotos.selfie;
  }

  // Animation flip avant la prochaine étape
  if (captureType === 'front') {
    const card = document.getElementById('idCard');
    setTimeout(() => {
      card.classList.add('flipped');
    }, 300);
  }

  nextStep();
    }

/*
async function confirmPhoto() {
  document.getElementById('previewStep').classList.remove('active');

  // Sauvegarde du cliché dans les champs cachés
  if (captureType === 'front') {
    document.getElementById('photo_front').value = capturedPhotos.front;
  } else if (captureType === 'back') {
    document.getElementById('photo_back').value = capturedPhotos.back;
  } else if (captureType === 'selfie') {
    document.getElementById('photo_selfie').value = capturedPhotos.selfie;

    // 🔹 Envoi au microservice Python pour vérification faciale
    try {
      const response = await fetch("/face-verify", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    image_base64: capturedPhotos.selfie,
    token: "{{ $token ?? 'demo_token' }}"
  })
      });

      const data = await response.json();

      console.log("Réponse DeepFace:", data);

      if (data.status === "ok" && data.confidence >= 0.98) {
        alert("✅ Vérification faciale réussie (" + (data.confidence * 100).toFixed(1) + "%)");
        nextStep(); // passe à l’étape suivante
      } else {
        alert("⚠️ Reprenez le selfie.\nConfiance: " + data.status +"-"+data.reason+ (data.confidence * 100).toFixed(1) + "%");
        // Revenir à la caméra selfie pour réessayer
        openCamera("selfie");
        return;
      }
    } catch (error) {
      console.error("Erreur lors de la vérification faciale:", error);
      alert("Erreur de connexion au service de reconnaissance. Réessayez.");
      openCamera("selfie");
      return;
    }
  }

  // Animation flip pour la carte après le recto
  if (captureType === 'front') {
    const card = document.getElementById('idCard');
    setTimeout(() => {
      card.classList.add('flipped');
    }, 300);
  }

  if (captureType !== 'selfie') {
    nextStep();
  }
}*/


//fin



    async function flipCamera() {
      stopCamera();
      currentCamera = currentCamera === 'user' ? 'environment' : 'user';

      const constraints = {
        video: {
          facingMode: currentCamera,
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      };

      try {
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('video');
        video.srcObject = stream;
      } catch (err) {
        console.error('Erreur changement caméra:', err);
      }
    }

    async function switchCameraMode(mode) {
      stopCamera();
      currentCamera = mode;

      // Update active button
      document.getElementById('frontCamBtn').classList.remove('active');
      document.getElementById('backCamBtn').classList.remove('active');

      if (mode === 'user') {
        document.getElementById('frontCamBtn').classList.add('active');
      } else {
        document.getElementById('backCamBtn').classList.add('active');
      }

      const constraints = {
        video: {
          facingMode: mode,
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      };

      try {
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        const video = document.getElementById('video');
        video.srcObject = stream;
      } catch (err) {
        console.error('Erreur changement caméra:', err);
        alert('Impossible de changer de caméra');
      }
    }
  </script>
</body>
</html>
