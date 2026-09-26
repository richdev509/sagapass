<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification - SAGAPASS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0D6EFD;
            --primary-dark: #0a58ca;
            --secondary: #1A202C;
            --text-muted: #718096;
            --border-color: #E2E8F0;
            --danger: #DC2626;
            --success: #16A34A;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', sans-serif; background: #F7FAFC; color: var(--secondary);
        }
        .card { width: min(90vw, 26rem); background: #fff; border: 1px solid var(--border-color); border-radius: 0.9rem; padding: 2rem; }
        .brand { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; font-size: 1.1rem; margin-bottom: 1.5rem; }
        .brand span { color: var(--primary); }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p.hint { color: var(--text-muted); font-size: 0.88rem; margin: 0 0 1.25rem; }
        .field { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; }
        input {
            width: 100%; padding: 0.65rem 0.8rem; border: 1px solid var(--border-color);
            border-radius: 0.5rem; font-family: inherit; font-size: 1.3rem; letter-spacing: 0.3em; text-align: center;
        }
        input:focus { outline: 2px solid var(--primary); outline-offset: 1px; }
        .error { color: var(--danger); font-size: 0.8rem; margin: 0 0 1rem; }
        .status { color: var(--success); font-size: 0.85rem; margin: 0 0 1rem; }
        .btn {
            width: 100%; border: none; background: var(--primary); color: #fff;
            padding: 0.8rem; border-radius: 0.6rem; font-weight: 700; font-size: 0.95rem; cursor: pointer;
        }
        .btn:hover { background: var(--primary-dark); }
        .footer-note { text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: 1.1rem; }
        .footer-note button { background: none; border: none; color: var(--primary); font: inherit; cursor: pointer; padding: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><span>SAGA</span>PASS - Partenaires</div>
        <h1>Vérification en 2 étapes</h1>
        <p class="hint">Un code à 6 chiffres a été envoyé par email. Il est valide 10 minutes.</p>

        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif

        @error('code') <p class="error">{{ $message }}</p> @enderror

        <form method="POST" action="{{ route('partner.otp.verify.submit') }}">
            @csrf
            <div class="field">
                <label for="code">Code de vérification</label>
                <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn">Vérifier</button>
        </form>

        <p class="footer-note">
            <form method="POST" action="{{ route('partner.otp.resend') }}" style="display:inline">
                @csrf
                <button type="submit">Renvoyer le code</button>
            </form>
        </p>
    </div>
</body>
</html>
