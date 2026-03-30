<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/article_functions.php';

$articles = [];
$error = '';
$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';

try {
    $articles = fetch_admin_articles();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

function render_article_title_html(array $article): string
{
  $titleHtml = trim((string)($article['titre'] ?? ''));

  if ($titleHtml === '' && isset($article['contenu'])) {
    $content = (string)$article['contenu'];

    if (preg_match('/<h[1-6][^>]*\sdata-field="titre"[^>]*>.*?<\/h[1-6]>/is', $content, $m)) {
      $titleHtml = trim($m[0]);
    }

    if ($titleHtml === '' && preg_match('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', $content, $m)) {
      $titleHtml = trim($m[0]);
    }
  }

  if ($titleHtml === '') {
    return 'Sans titre';
  }

  // Keep heading/inline formatting from TinyMCE but remove risky tags.
  $allowed = '<h1><h2><h3><h4><h5><h6><p><strong><em><span><u><b><i><sup><sub><small><br>';
  $safeHtml = strip_tags($titleHtml, $allowed);
  $safeHtml = preg_replace('/\s+/', ' ', $safeHtml);

  return trim((string)$safeHtml);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Liste des articles</title>
  <link rel="stylesheet" href="/assets/css/admin-articles.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>BackOffice - Articles</h1>
        <div class="muted">Connecte en tant que: <?= htmlspecialchars($_SESSION['user']['email'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div class="actions">
        <a class="btn primary" href="/admin/articles/create.php">Ajouter un nouvel article</a>
        <a class="btn" href="/admin/categorie/index.php">Gestion categorie</a>
        <a class="btn" href="/">Voir le FrontOffice</a>
        <a class="btn" href="/auth/logout.php">Deconnexion</a>
      </div>
    </div>

    <div class="card">
      <?php if ($created): ?>
        <div class="success">Article ajoute avec succes.</div>
      <?php endif; ?>

      <?php if ($updated): ?>
        <div class="success">Article modifie avec succes.</div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="error">Erreur base de donnees: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php elseif (count($articles) === 0): ?>
        <p>Aucun article pour le moment.</p>
      <?php else: ?>
        <ul class="article-list compact">
          <?php foreach ($articles as $article): ?>
            <li class="article-item">
              <?php if (!empty($article['image_path'])): ?>
                <div class="article-thumb-wrap">
                  <img class="article-thumb" src="<?= htmlspecialchars((string)$article['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Image article <?= (int)$article['Id_article'] ?>">
                  <div class="muted compact-meta">Media: <?= htmlspecialchars((string)$article['image_path'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              <?php endif; ?>
              <div class="article-title"><?= render_article_title_html($article) ?></div>
              <div class="muted compact-meta">
                ID <?= (int)$article['Id_article'] ?>
                | Auteur: <?= htmlspecialchars((string)($article['auteur'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                | Date evenement: <?= htmlspecialchars((string)($article['date_evenement'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                | Maj: <?= htmlspecialchars((string)($article['updated_at'] ?: $article['created_at'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div class="actions" style="margin-top: 8px;">
                <a class="btn" href="/admin/articles/create.php?id=<?= (int)$article['Id_article'] ?>">Modifier</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
