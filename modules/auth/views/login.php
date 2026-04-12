<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sign In — PDL Helpdesk') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Inter', system-ui, sans-serif; }

        /* ════════════════════════════════════════
           ANIMATED GRADIENT BACKGROUND
        ════════════════════════════════════════ */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(-45deg, #0d1b2a, #1a3a4a, #0a2a1f, #162a45, #0d2233);
            background-size: 400% 400%;
            animation: gradientMove 18s ease infinite;
            overflow: hidden;
        }

        @keyframes gradientMove {
            0%   { background-position: 0%   50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0%   50%; }
        }

        /* ── Subtle floating orbs (pure CSS, no JS) ── */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(13,148,136,0.18) 0%, transparent 70%);
            top: -120px; left: -120px;
            animation: orbDrift1 20s ease-in-out infinite alternate;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(30,58,95,0.25) 0%, transparent 70%);
            bottom: -100px; right: -100px;
            animation: orbDrift2 16s ease-in-out infinite alternate;
        }

        @keyframes orbDrift1 {
            from { transform: translate(0, 0); }
            to   { transform: translate(60px, 80px); }
        }
        @keyframes orbDrift2 {
            from { transform: translate(0, 0); }
            to   { transform: translate(-50px, -60px); }
        }

        /* ════════════════════════════════════════
           PAGE FADE-IN
        ════════════════════════════════════════ */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ════════════════════════════════════════
           GLASSMORPHISM CARD
        ════════════════════════════════════════ */
        .glass-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 20px;
            padding: 40px 36px 36px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 1px 0 rgba(255, 255, 255, 0.08) inset;
        }

        /* ── Brand logo mark ── */
        .brand-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0d9488 0%, #1e3a5f 100%);
            border: 1px solid rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 16px rgba(13,148,136,0.35);
        }
        .brand-icon svg { width: 24px; height: 24px; color: white; }

        /* ── Headings ── */
        .card-title {
            text-align: center;
            font-size: 1.375rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .card-subtitle {
            text-align: center;
            font-size: 0.8125rem;
            color: rgba(148, 163, 184, 0.8);
            margin-bottom: 28px;
        }

        /* ════════════════════════════════════════
           ERROR ALERT
        ════════════════════════════════════════ */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
        }
        .alert-error svg { width: 16px; height: 16px; color: #f87171; flex-shrink: 0; margin-top: 1px; }
        .alert-error p  { font-size: 0.8125rem; color: #fca5a5; line-height: 1.4; }

        /* ════════════════════════════════════════
           FORM FIELDS
        ════════════════════════════════════════ */
        .field-group { margin-bottom: 16px; }

        .field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(203, 213, 225, 0.9);
            margin-bottom: 7px;
        }

        .field-wrap { position: relative; }

        .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            transition: color 0.2s ease;
        }
        .field-icon svg { width: 16px; height: 16px; color: rgba(148,163,184,0.6); }

        /* INPUT — glass style with focus glow */
        .field-input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 11px;
            font-size: 0.875rem;
            font-family: inherit;
            color: #f1f5f9;
            outline: none;
            transition:
                border-color 0.2s ease,
                background   0.2s ease,
                box-shadow   0.2s ease;
        }

        .field-input::placeholder { color: rgba(148, 163, 184, 0.45); }

        /* FOCUS GLOW — the key animation */
        .field-input:focus {
            border-color: rgba(13, 148, 136, 0.7);
            background: rgba(255, 255, 255, 0.1);
            box-shadow:
                0 0 0 3px rgba(13, 148, 136, 0.18),
                0 0 16px rgba(13, 148, 136, 0.12);
        }

        /* Icon tint on focus */
        .field-wrap:focus-within .field-icon svg {
            color: rgba(13, 148, 136, 0.8);
        }

        /* Password toggle button */
        .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            color: rgba(148, 163, 184, 0.5);
            transition: color 0.18s ease;
            line-height: 0;
        }
        .pwd-toggle:hover { color: rgba(203, 213, 225, 0.85); }
        .pwd-toggle svg   { width: 16px; height: 16px; }

        /* ════════════════════════════════════════
           SUBMIT BUTTON — hover & press animations
        ════════════════════════════════════════ */
        .btn-submit {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            background: linear-gradient(135deg, #0d9488 0%, #0c6b61 100%);
            color: #fff;
            border: none;
            border-radius: 11px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition:
                transform    0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow   0.18s ease,
                background   0.18s ease;
        }

        /* Shine overlay */
        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.16) 0%, transparent 55%);
            opacity: 0;
            transition: opacity 0.18s ease;
            border-radius: inherit;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(13, 148, 136, 0.42);
        }
        .btn-submit:hover::before { opacity: 1; }

        .btn-submit:active {
            transform: translateY(0px) scale(0.985);
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-content, .btn-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .btn-loader { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        /* ════════════════════════════════════════
           FOOTER
        ════════════════════════════════════════ */
        .card-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status-dot {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: rgba(148, 163, 184, 0.6);
        }
        .status-dot span:first-child {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
            animation: pulse 2.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }
        .version-tag {
            font-size: 0.7rem;
            color: rgba(148, 163, 184, 0.35);
            letter-spacing: 0.04em;
        }

        /* Below-card text */
        .below-card {
            text-align: center;
            margin-top: 18px;
            font-size: 0.75rem;
            color: rgba(148, 163, 184, 0.4);
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <!-- ═══════════════════════════════
         GLASS CARD
    ═══════════════════════════════ -->
    <div class="glass-card">

        <!-- Brand mark -->
        <div class="brand-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                      d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>

        <!-- Headings -->
        <h1 class="card-title">PDL Helpdesk</h1>
        <p class="card-subtitle">Sign in to your account to continue</p>

        <!-- Error message -->
        <?php if (!empty($error)): ?>
        <div class="alert-error">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form id="loginForm" method="POST" action="<?= BASE_URL ?>?page=auth/login" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

            <!-- Username / Email -->
            <div class="field-group">
                <label class="field-label" for="identifier">Username or Email</label>
                <div class="field-wrap">
                    <span class="field-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        class="field-input"
                        placeholder="username or email"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="field-group" style="margin-bottom: 22px;">
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <span class="field-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="field-input"
                        style="padding-right: 42px;"
                        placeholder="enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="pwd-toggle" id="pwdToggle" tabindex="-1">
                        <svg id="eyeShow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eyeHide" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="btn-content" id="btnText">Sign In</span>
                <span class="btn-loader" id="btnLoader">
                    <span class="spinner"></span>
                    Signing in…
                </span>
            </button>
        </form>

        <!-- Footer -->
        <div class="card-footer">
            <div class="status-dot">
                <span></span>
                <span>System online</span>
            </div>
            <span class="version-tag">PDL HELPDESK v1.0</span>
        </div>

    </div><!-- /glass-card -->

    <p class="below-card">Pantex Dress Ltd. &nbsp;·&nbsp; Internal use only &nbsp;·&nbsp; <?= date('Y') ?></p>

</div><!-- /login-wrapper -->

<script>
    // ── Password visibility toggle ──────────────────────────
    document.getElementById('pwdToggle').addEventListener('click', function () {
        var pwd  = document.getElementById('password');
        var show = document.getElementById('eyeShow');
        var hide = document.getElementById('eyeHide');
        var isPass = pwd.type === 'password';
        pwd.type        = isPass ? 'text' : 'password';
        show.style.display = isPass ? 'none'  : '';
        hide.style.display = isPass ? ''      : 'none';
    });

    // ── Submit: show loading state ──────────────────────────
    document.getElementById('loginForm').addEventListener('submit', function () {
        var btn    = document.getElementById('submitBtn');
        var text   = document.getElementById('btnText');
        var loader = document.getElementById('btnLoader');
        btn.disabled        = true;
        text.style.display  = 'none';
        loader.style.display = 'flex';
    });
</script>

</body>
</html>
