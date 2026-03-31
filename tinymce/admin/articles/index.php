<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/article_functions.php';

$articles = [];
$error    = '';
$created  = isset($_GET['created']) && $_GET['created'] === '1';
$updated  = isset($_GET['updated']) && $_GET['updated'] === '1';

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

    if ($titleHtml === '') return 'Sans titre';

    $allowed  = '<h1><h2><h3><h4><h5><h6><p><strong><em><span><u><b><i><sup><sub><small><br>';
    $safeHtml = strip_tags($titleHtml, $allowed);
    $safeHtml = preg_replace('/\s+/', ' ', $safeHtml);

    return trim((string)$safeHtml);
}

$activePage = 'articles-list';
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

<?php require_once __DIR__ . '/../_sidebar.php'; ?>

<div class="wrap">
  <div class="page-head">
    <h1>Liste des articles</h1>
    <div class="muted">Connecté en tant que&nbsp;: <?= htmlspecialchars($_SESSION['user']['email'], ENT_QUOTES, 'UTF-8') ?></div>
  </div>

  <div class="card">
    <div class="card-title">
      Tous les articles
      <?php if (count($articles) > 0): ?>
        <span class="badge" style="margin-left:8px;"><?= count($articles) ?></span>
      <?php endif; ?>
    </div>

    <?php if ($created): ?>
      <div class="success">Article ajouté avec succès.</div>
    <?php endif; ?>

    <?php if ($updated): ?>
      <div class="success">Article modifié avec succès.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="error">Erreur base de données&nbsp;: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (count($articles) === 0): ?>
      <p class="muted">Aucun article pour le moment.</p>
    <?php else: ?>
      <ul class="article-list">
        <?php foreach ($articles as $article): ?>
          <li class="article-item">
            <?php
              $slug = trim((string)($article['slug'] ?? ''));
              $viewUrl = $slug !== ''
              ? '/article/' . rawurlencode($slug)
                  : '';
            ?>
            <?php if (!empty($article['image_path'])): ?>
              <div class="article-thumb-wrap">
                <?php $imageAlt = trim((string)($article['image_alt_text'] ?? '')); ?>
                <img
                  class="article-thumb"
                  src="<?= htmlspecialchars((string)$article['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($imageAlt !== '' ? $imageAlt : ('Image article ' . (int)$article['Id_article']), ENT_QUOTES, 'UTF-8') ?>"
                >
                <div class="compact-meta">
                  <?php if ($viewUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Voir plus</a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="article-info">
              <div class="article-title"><?= render_article_title_html($article) ?></div>
              <div class="article-meta-row">
                <span>ID <?= (int)$article['Id_article'] ?></span>
                <span>Auteur&nbsp;: <?= htmlspecialchars((string)($article['auteur'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Événement&nbsp;: <?= htmlspecialchars((string)($article['date_evenement'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Màj&nbsp;: <?= htmlspecialchars((string)($article['updated_at'] ?: $article['created_at'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>

            <div class="article-actions-col">
              <a class="btn primary" href="/admin/articles/create.php?id=<?= (int)$article['Id_article'] ?>">Modifier</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
