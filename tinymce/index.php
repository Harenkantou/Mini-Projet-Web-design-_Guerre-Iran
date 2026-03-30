<?php
session_start();
require_once __DIR__ . '/db.php';

$articles = [];
$categories = [];
$dbError = '';
$selectedCategorySlug = trim((string)($_GET['category'] ?? ''));
$selectedCategory = null;

try {
    $conn = db_connect();

    // Récupération des catégories (menu + statistiques)
    $sql = 'SELECT c.Id_categorie, c.name, c.description, c.slug, COUNT(ca.Id_categorie_article) AS article_count
            FROM categorie c
            LEFT JOIN categorie_article ca ON ca.Id_categorie = c.Id_categorie
            GROUP BY c.Id_categorie, c.name, c.description, c.slug
            ORDER BY c.name ASC';
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    // Si catégorie demandée, on récupère la catégorie
    if ($selectedCategorySlug !== '') {
        $stmt = $conn->prepare('SELECT Id_categorie, name FROM categorie WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $selectedCategorySlug);
        $stmt->execute();
        $selectedCategory = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }

    // Requête articles
    if (!empty($selectedCategory) && isset($selectedCategory['Id_categorie'])) {
        $categoryId = (int)$selectedCategory['Id_categorie'];
        $stmt = $conn->prepare('SELECT a.Id_article, a.titre, a.contenu, a.auteur, a.created_at
                                FROM article a
                                INNER JOIN categorie_article ca ON ca.Id_article = a.Id_article
                                WHERE ca.Id_categorie = ?
                                ORDER BY COALESCE(a.updated_at, a.created_at) DESC
                                LIMIT 20');
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
        $stmt->close();
    } else {
        $sql = 'SELECT Id_article, titre, contenu, auteur, created_at FROM article ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 20';
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $articles[] = $row;
            }
        }
    }

    $conn->close();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$isAdmin = isset($_SESSION['user']);
$selectedCategoryName = $selectedCategory['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FrontOffice - Infos Guerre Iran/Israel</title>
  <style>
    body { margin:0; font-family:Verdana, sans-serif; background:#f5f7fb; color:#1c2940; }
    .wrap { max-width:900px; margin:0 auto; padding:24px; }
    .header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    h1 { margin:0; font-size:28px; }
    .btn { display:inline-block; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:700; border:1px solid #174ea6; color:#174ea6; background:#fff; }
    .btn.primary { background:#174ea6; color:#fff; }
    .btn.category { margin:4px 4px 4px 0; }
    .btn.category.active { background:#174ea6; color:#fff; }
    .card { margin-top:20px; background:#fff; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.08); padding:20px; }
    .muted { color:#5b6f8f; font-size:14px; }
    .category-menu { margin-top:16px; }
    ul { margin:12px 0 0; padding-left:18px; }
    li { margin-bottom:12px; }
    .article-title { font-weight:700; }
    .article-title .muted { font-weight:400; font-size:12px; }
    .error { color:#7a1020; background:#fde7e9; border:1px solid #f5c2c7; border-radius:8px; padding:10px; margin-top:10px; }
    h2 { margin:0 0 10px 0; color:#1c2940; font-size:24px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>FrontOffice - Liste des articles</h1>
      <div>
        <?php if ($isAdmin): ?>
          <a class="btn" href="/admin/articles/index.php">Espace admin</a>
          <a class="btn primary" href="/auth/logout.php">Deconnexion</a>
        <?php else: ?>
          <a class="btn primary" href="/auth/login.php">Admin</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="category-menu">
      <a class="btn category<?= $selectedCategorySlug === '' ? ' active' : '' ?>" href="/">Tous</a>
      <?php foreach ($categories as $category): ?>
        <a class="btn category<?= $selectedCategorySlug === $category['slug'] ? ' active' : '' ?>"
           href="?category=<?= urlencode($category['slug']) ?>">
          <?= htmlspecialchars($category['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          (<?= (int)($category['article_count'] ?? 0) ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <?php if ($selectedCategoryName !== ''): ?>
        <h2>Articles dans « <?= htmlspecialchars($selectedCategoryName, ENT_QUOTES, 'UTF-8') ?> »</h2>
      <?php else: ?>
        <h2>Tous les articles</h2>
      <?php endif; ?>

      <p class="muted">Cette page affiche les articles selon la catégorie sélectionnée.</p>

      <?php if ($dbError !== ''): ?>
        <div class="error">Erreur base de données: <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
      <?php elseif (count($articles) === 0): ?>
        <p>Aucun article pour le moment.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($articles as $article): ?>
            <li>
              <div class="article-title"><?= $article['titre'] ?: 'Sans titre' ?></div>
              <div class="muted">
                Auteur: <?= htmlspecialchars($article['auteur'] ?: 'Inconnu', ENT_QUOTES, 'UTF-8') ?>
                | Date: <?= htmlspecialchars((string)($article['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="article-content"><?= $article['contenu'] ?? '' ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    
  </div>
</body>
</html>
