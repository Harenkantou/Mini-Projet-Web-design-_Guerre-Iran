<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/article_functions.php';

$articles = [];
$error    = '';
$created  = isset($_GET['created']) && $_GET['created'] === '1';
$updated  = isset($_GET['updated']) && $_GET['updated'] === '1';
$searchKeyword = trim((string)($_GET['q'] ?? ''));
$selectedEventDate = trim((string)($_GET['date'] ?? ''));
$selectedCategorySlug = trim((string)($_GET['category'] ?? ''));
$categories = [];
$selectedCategoryName = '';

if ($selectedEventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedEventDate)) {
  $selectedEventDate = '';
}

try {
  $categories = fetch_admin_categories_menu();
  foreach ($categories as $category) {
    if ((string)($category['slug'] ?? '') === $selectedCategorySlug) {
      $selectedCategoryName = (string)($category['name'] ?? '');
      break;
    }
  }

  $articles = fetch_admin_articles(50, $searchKeyword, $selectedEventDate, $selectedCategorySlug);
} catch (Throwable $e) {
    $error = $e->getMessage();
}

function admin_articles_url(array $params = []): string
{
  $clean = [];
  foreach ($params as $key => $value) {
    $value = trim((string)$value);
    if ($value !== '') {
      $clean[$key] = $value;
    }
  }

  return '/admin/articles/' . ($clean ? ('?' . http_build_query($clean)) : '');
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
  <meta name="description" content="Back-office : gestion, recherche et filtrage des articles par catégorie et date.">
  <meta name="robots" content="noindex,nofollow,noarchive">
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
    <h2 class="card-title">
      <?= $selectedCategoryName !== ''
          ? ('Articles dans « ' . htmlspecialchars($selectedCategoryName, ENT_QUOTES, 'UTF-8') . ' »')
          : 'Tous les articles' ?>
    </h2>

    <?php if ($created): ?>
      <div class="success">Article ajouté avec succès.</div>
    <?php endif; ?>

    <?php if ($updated): ?>
      <div class="success">Article modifié avec succès.</div>
    <?php endif; ?>

    <?php if (count($categories) > 0): ?>
      <div style="margin-bottom:14px; display:flex; flex-wrap:wrap; gap:8px;">
        <a
          class="btn<?= $selectedCategorySlug === '' ? ' primary' : '' ?>"
          href="<?= htmlspecialchars(admin_articles_url(['q' => $searchKeyword, 'date' => $selectedEventDate]), ENT_QUOTES, 'UTF-8') ?>"
        >
          Toutes les catégories
        </a>
        <?php foreach ($categories as $category): ?>
          <?php
            $catSlug = trim((string)($category['slug'] ?? ''));
            $isActiveCategory = $selectedCategorySlug !== '' && $selectedCategorySlug === $catSlug;
          ?>
          <?php if ($catSlug !== ''): ?>
            <a
              class="btn<?= $isActiveCategory ? ' primary' : '' ?>"
              href="<?= htmlspecialchars(admin_articles_url(['category' => $catSlug, 'q' => $searchKeyword, 'date' => $selectedEventDate]), ENT_QUOTES, 'UTF-8') ?>"
            >
              <?= htmlspecialchars((string)($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form class="form-grid" method="get" action="/admin/articles/" style="margin-bottom:16px;">
      <?php if ($selectedCategorySlug !== ''): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug, ENT_QUOTES, 'UTF-8') ?>">
      <?php endif; ?>
      <div>
        <label for="q">Recherche (mot-clé)</label>
        <input
          type="text"
          id="q"
          name="q"
          placeholder="Titre, contenu, auteur, slug"
          value="<?= htmlspecialchars($searchKeyword, ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>
      <div>
        <label for="date">Date d'événement</label>
        <input
          type="date"
          id="date"
          name="date"
          value="<?= htmlspecialchars($selectedEventDate, ENT_QUOTES, 'UTF-8') ?>"
        >
      </div>
      <div class="actions full" style="margin-top:0; padding-top:0; border-top:0;">
        <button class="btn primary" type="submit">Filtrer</button>
        <a class="btn" href="<?= htmlspecialchars(admin_articles_url(['category' => $selectedCategorySlug]), ENT_QUOTES, 'UTF-8') ?>">Réinitialiser</a>
      </div>
    </form>

    <?php if ($error !== ''): ?>
      <div class="error">Erreur base de données&nbsp;: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (count($articles) === 0): ?>
      <p class="muted">Aucun article pour le moment.</p>
    <?php else: ?>
      <ul class="article-list">
        <?php foreach ($articles as $article): ?>
          <li class="article-item">
            <?php
              $articleId = (int)($article['Id_article'] ?? 0);
              $slug = trim((string)($article['slug'] ?? ''));
              $viewUrl = $slug !== ''
                  ? '/article/' . rawurlencode($slug)
                  : '/article/id/' . $articleId;
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
                  <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Voir plus</a>
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
              <a class="btn" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Voir plus</a>
              <a class="btn primary" href="/admin/articles/modifier/<?= (int)$article['Id_article'] ?>">Modifier</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
