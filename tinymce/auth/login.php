<?php
session_start();
require_once __DIR__ . '/auth_functions.php';

if (isset($_SESSION['user'])) {
  header('Location: /admin/articles');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir l\'email et le mot de passe.';
    } else {
        try {
            if (login_user($email, $password)) {
              header('Location: /admin/articles');
                exit();
            }
            $error = 'Identifiants invalides.';
        } catch (Throwable $e) {
            $error = 'Erreur serveur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Back Office</title>
  <meta name="description" content="Connexion sécurisée à l'espace d'administration.">
  <meta name="robots" content="noindex,nofollow,noarchive">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth.css">
  <style>
    /* Fallback inline au cas où le CSS externe ne charge pas */
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%;width:100%}
    body{
      font-family:'DM Sans',system-ui,sans-serif;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      background-color:#0a0f1a;
      background-image:
        radial-gradient(ellipse 80% 60% at 15% 50%,rgba(185,28,28,.18) 0%,transparent 65%),
        radial-gradient(ellipse 60% 80% at 85% 20%,rgba(30,41,80,.6) 0%,transparent 60%);
      padding:24px;
      position:relative;
      overflow:hidden;
    }
    body::before{
      content:'ADMIN';
      position:fixed;
      bottom:-40px;right:-20px;
      font-family:'Playfair Display',Georgia,serif;
      font-size:220px;font-weight:800;
      color:rgba(255,255,255,.025);
      line-height:1;pointer-events:none;user-select:none;letter-spacing:-8px;
    }
    .login-card{
      position:relative;z-index:1;
      width:100%;max-width:420px;
      background:#fff;border-radius:12px;overflow:hidden;
      box-shadow:0 0 0 1px rgba(255,255,255,.06),0 24px 60px rgba(0,0,0,.6),0 4px 16px rgba(0,0,0,.4);
      animation:cardIn .4s ease both;
    }
    @keyframes cardIn{
      from{opacity:0;transform:translateY(16px) scale(.98)}
      to{opacity:1;transform:translateY(0) scale(1)}
    }
    .login-header{
      background:linear-gradient(135deg,#991b1b 0%,#b91c1c 50%,#c62020 100%);
      padding:32px 32px 28px;text-align:center;position:relative;overflow:hidden;
    }
    .login-header::before{
      content:'';position:absolute;top:-40px;right:-40px;
      width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.06);
    }
    .login-header::after{
      content:'';position:absolute;bottom:-50px;left:-30px;
      width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.04);
    }
    .login-site-name{
      font-family:'Playfair Display',Georgia,serif;
      font-size:24px;font-weight:800;color:#fff;
      letter-spacing:-.5px;line-height:1;position:relative;z-index:1;
    }
    .login-site-sub{
      font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;
      color:rgba(255,255,255,.5);margin-top:6px;position:relative;z-index:1;
    }
    .login-lock{
      width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      margin:14px auto 0;position:relative;z-index:1;
      border:1px solid rgba(255,255,255,.2);
    }
    .login-body{padding:28px 32px 24px;}
    .login-greeting{
      font-family:'Playfair Display',Georgia,serif;
      font-size:21px;font-weight:700;color:#0d1117;margin-bottom:4px;
    }
    .login-desc{font-size:13px;color:#7a8a9a;margin-bottom:24px;}
    .field{margin-bottom:16px;}
    .field label{
      display:block;font-size:11.5px;font-weight:700;letter-spacing:.6px;
      text-transform:uppercase;color:#2c3a4a;margin-bottom:8px;
    }
    .field-wrap{position:relative;}
    .field-icon{
      position:absolute;left:13px;top:50%;transform:translateY(-50%);
      color:#9ba8ba;display:flex;align-items:center;pointer-events:none;
    }
    .field input{
      width:100%;padding:11px 14px 11px 40px;
      border:1.5px solid #e2e8f0;border-radius:8px;
      font-family:'DM Sans',sans-serif;font-size:14px;color:#0d1117;
      background:#f8fafc;outline:none;
      transition:border-color .2s,box-shadow .2s,background .2s;
    }
    .field input:hover{border-color:#c8d4e0;}
    .field input:focus{border-color:#b91c1c;box-shadow:0 0 0 3px rgba(185,28,28,.12);background:#fff;}
    .field input::placeholder{color:#b0bcc8;font-size:13.5px;}
    .login-btn{
      width:100%;padding:13px;margin-top:6px;
      background:linear-gradient(135deg,#991b1b 0%,#b91c1c 100%);
      color:#fff;border:none;border-radius:8px;
      font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;
      letter-spacing:.3px;cursor:pointer;position:relative;overflow:hidden;
      transition:transform .1s,box-shadow .15s;
      box-shadow:0 4px 16px rgba(185,28,28,.35);
    }
    .login-btn:hover{box-shadow:0 6px 24px rgba(185,28,28,.45);transform:translateY(-1px);}
    .login-btn:active{transform:translateY(0);}
    .login-error{
      margin-top:14px;padding:11px 14px;
      background:#fef2f2;border:1px solid #fca5a5;border-left:4px solid #b91c1c;
      border-radius:6px;color:#7f1d1d;font-size:13px;font-weight:500;
      display:flex;align-items:center;gap:8px;
      animation:shake .3s ease;
    }
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
    .login-footer{
      padding:14px 32px 20px;border-top:1px solid #f0f4f8;
      display:flex;align-items:center;justify-content:space-between;
      gap:12px;flex-wrap:wrap;background:#fafbfc;
    }
    .login-hint{font-size:11.5px;color:#9ba8ba;line-height:1.5;}
    .login-hint code{background:#e8edf5;padding:2px 6px;border-radius:3px;font-size:11px;color:#3a4a5c;}
    .login-back{
      display:inline-flex;align-items:center;gap:5px;
      font-size:12.5px;font-weight:600;color:#b91c1c;
      text-decoration:none;white-space:nowrap;transition:opacity .15s;
    }
    .login-back:hover{opacity:.7;}
  </style>
</head>
<body>

  <div class="login-card">

    <div class="login-header">
      <div class="login-site-name">Iran &ndash; Israël Info</div>
      <div class="login-site-sub">Espace Administration</div>
      <div class="login-lock">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
      </div>
    </div>

    <div class="login-body">
      <h1 class="login-greeting">Connexion</h1>
      <p class="login-desc">Accès réservé aux administrateurs.</p>

      <form method="post" action="/admin/login">

        <div class="field">
          <label for="email">Adresse e-mail</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/>
                <path d="M2 7l10 7 10-7"/>
              </svg>
            </span>
            <input id="email" name="email" type="email" required
              placeholder="admin@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="email">
          </div>
        </div>

        <div class="field">
          <label for="password">Mot de passe</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </span>
            <input id="password" name="password" type="password" required
              placeholder="••••••••"
              autocomplete="current-password">
          </div>
        </div>

        <button class="login-btn" type="submit">Se connecter</button>

        <?php if ($error !== ''): ?>
          <div class="login-error">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
            </svg>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

      </form>
    </div>

    <div class="login-footer">
      <span class="login-hint">
        Démo&nbsp;: <code>admin@local.dev</code> / <code>admin123</code>
      </span>
      <a class="login-back" href="/accueil">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Retour au site
      </a>
    </div>

  </div>

</body>
</html>
