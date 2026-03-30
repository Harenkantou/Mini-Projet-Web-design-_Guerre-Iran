<?php
session_start();
require_once __DIR__ . '/db.php';

$articles = [];
$dbError = '';

try {
    $conn = db_connect();
    $sql = 'SELECT Id_article, titre, auteur, created_at FROM article ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 20';
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
    }
    $conn->close();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$isAdmin = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FrontOffice - Infos Guerre Iran/Israel</title>
  <style>
    body {
      margin: 0;
      font-family: Verdana, sans-serif;
      background: #f5f7fb;
      color: #1c2940;
    }
    .wrap {
      max-width: 900px;
      margin: 0 auto;
      padding: 24px;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    h1 {
      margin: 0;
      font-size: 28px;
    }
    .btn {
      display: inline-block;
      text-decoration: none;
      padding: 10px 14px;
      border-radius: 8px;
      font-weight: 700;
      border: 1px solid #174ea6;
      color: #174ea6;
      background: #fff;
    }
    .btn.primary {
      background: #174ea6;
      color: #fff;
    }
    .card {
      margin-top: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      padding: 20px;
    }
    .muted {
      color: #5b6f8f;
      font-size: 14px;
    }
    ul {
      margin: 12px 0 0;
      padding-left: 18px;
    }
    li {
      margin-bottom: 12px;
    }
    .article-title {
      font-weight: 700;
    }
    .error {
      color: #7a1020;
      background: #fde7e9;
      border: 1px solid #f5c2c7;
      border-radius: 8px;
      padding: 10px;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>FrontOffice - Liste des articles</h1>
      <div>
        <?php if ($isAdmin): ?>
          <a class="btn" href="/backoffice.php">Espace admin</a>
          <a class="btn primary" href="/auth/logout.php">Deconnexion</a>
        <?php else: ?>
          <a class="btn primary" href="/auth/login.php">Admin</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <p class="muted">Cette page affiche une liste simple des articles de la base de donnees.</p>

      <?php if ($dbError !== ''): ?>
        <div class="error">Erreur base de donnees: <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
      <?php elseif (count($articles) === 0): ?>
        <p>Aucun article pour le moment.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($articles as $article): ?>
            <li>
              <div class="article-title"><?= htmlspecialchars($article['titre'] ?: 'Sans titre', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="muted">
                Auteur: <?= htmlspecialchars($article['auteur'] ?: 'Inconnu', ENT_QUOTES, 'UTF-8') ?>
                | Date: <?= htmlspecialchars((string)($article['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>