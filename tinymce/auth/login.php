<?php
session_start();
require_once __DIR__ . '/auth_functions.php';

if (isset($_SESSION['user'])) {
  header('Location: /admin/articles/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir email et mot de passe.';
    } else {
        try {
            if (login_user($email, $password)) {
                header('Location: /admin/articles/index.php');
                exit();
            }

            $error = 'Identifiants invalides.';
        } catch (Throwable $e) {
            $error = 'Erreur serveur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion BackOffice</title>
  <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
  <form class="card" method="post" action="/auth/login.php">
    <h1>Connexion BackOffice</h1>
    <p>Connectez-vous pour gerer les contenus.</p>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" required>

    <button type="submit">Se connecter</button>

    <?php if ($error !== ''): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="hint">
      Compte de demo: admin@local.dev / admin123
    </div>
    <div class="back"><a href="/">Retour a l'accueil</a></div>
  </form>
</body>
</html>