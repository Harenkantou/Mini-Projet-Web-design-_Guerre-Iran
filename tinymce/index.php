<?php
session_start();
require_once __DIR__ . '/db.php';

$articles             = [];
$categories           = [];
$dbError              = '';
$selectedCategorySlug = trim((string)($_GET['category'] ?? ''));
$searchKeyword        = trim((string)($_GET['q'] ?? ''));
$selectedEventDate    = trim((string)($_GET['date'] ?? ''));
$selectedCategory     = null;

if ($selectedEventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedEventDate)) {
    $selectedEventDate = '';
}

try {
    $conn = db_connect();

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

    if ($selectedCategorySlug !== '') {
        $stmt = $conn->prepare('SELECT Id_categorie, name FROM categorie WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $selectedCategorySlug);
        $stmt->execute();
        $selectedCategory = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }

    $categoryId  = (!empty($selectedCategory) && isset($selectedCategory['Id_categorie']))
        ? (int)$selectedCategory['Id_categorie'] : 0;
    $keywordLike = '%' . $searchKeyword . '%';

    $stmt = $conn->prepare(
        'SELECT a.Id_article, a.slug, a.titre, a.contenu, a.auteur, a.created_at, a.date_evenement,
                MAX(m.path) AS image_path
         FROM article a
         LEFT JOIN categorie_article ca ON ca.Id_article = a.Id_article
         LEFT JOIN media_article ma ON ma.Id_article = a.Id_article
         LEFT JOIN media m ON m.Id_media = ma.Id_media
         WHERE (? = 0 OR ca.Id_categorie = ?)
           AND (? = "" OR (a.titre LIKE ? OR a.contenu LIKE ? OR a.auteur LIKE ? OR a.slug LIKE ?))
           AND (? = "" OR a.date_evenement = ?)
         GROUP BY a.Id_article, a.slug, a.titre, a.contenu, a.auteur, a.created_at, a.date_evenement
         ORDER BY COALESCE(a.updated_at, a.created_at) DESC
         LIMIT 20'
    );
    $stmt->bind_param(
        'iisssssss',
        $categoryId, $categoryId,
        $searchKeyword, $keywordLike, $keywordLike, $keywordLike, $keywordLike,
        $selectedEventDate, $selectedEventDate
    );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$isAdmin              = isset($_SESSION['user']);
$selectedCategoryName = $selectedCategory['name'] ?? '';
$baseFilters          = [];
if ($searchKeyword !== '')   $baseFilters['q']    = $searchKeyword;
if ($selectedEventDate !== '') $baseFilters['date'] = $selectedEventDate;

function category_url(string $slug, array $filters = []): string
{
  $base = '/accueil/categorie/' . rawurlencode($slug);
  return !empty($filters) ? ($base . '?' . http_build_query($filters)) : $base;
}

function article_preview_text(string $html, int $maxLen = 180): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if ($text === '') return '';
    if (mb_strlen($text, 'UTF-8') <= $maxLen) return $text;
    return rtrim(mb_substr($text, 0, $maxLen, 'UTF-8')) . '…';
}

function article_title_safe(string $html): string
{
    if (trim($html) === '') return 'Sans titre';
    $allowed = '<h1><h2><h3><h4><h5><h6><p><strong><em><span><b><i><br>';
    $safe    = strip_tags($html, $allowed);
    $safe    = preg_replace('/\s+/', ' ', $safe) ?? '';
    return trim($safe) !== '' ? trim($safe) : 'Sans titre';
}

function article_url(array $article): string
{
  $slug = trim((string)($article['slug'] ?? ''));
  if ($slug !== '') {
    return '/article/' . rawurlencode($slug);
  }

  return '/article/id/' . (int)($article['Id_article'] ?? 0);
}

function absolute_url_from_path(string $path): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';

    return $scheme . '://' . $host . $path;
}

$today = (new DateTime())->format('d F Y');

$canonicalPath = $selectedCategorySlug !== ''
    ? category_url($selectedCategorySlug, $baseFilters)
    : ('/accueil' . (!empty($baseFilters) ? ('?' . http_build_query($baseFilters)) : ''));
$canonicalUrl = absolute_url_from_path($canonicalPath);

$pageTitle = 'Iran–Israël Info — Actualités & Analyses';
if ($selectedCategoryName !== '') {
    $pageTitle = $selectedCategoryName . ' — Iran–Israël Info';
}
if ($searchKeyword !== '') {
    $pageTitle .= ' | Recherche: ' . $searchKeyword;
}

$metaDescription = 'Suivez en temps réel les développements du conflit Iran–Israël : analyses géopolitiques, décryptages et reportages.';
if ($selectedCategoryName !== '') {
    $metaDescription = 'Articles de la catégorie ' . $selectedCategoryName . ' sur Iran–Israël Info.';
}
if ($searchKeyword !== '') {
    $metaDescription = 'Résultats de recherche pour "' . $searchKeyword . '" sur Iran–Israël Info.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="/assets/css/front.css">
</head>
<body>

<!-- ══════════ MASTHEAD ══════════ -->
<header class="masthead">
  <div class="masthead-inner">
    <div class="masthead-top">
      <span class="masthead-date"><?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?></span>
      <div class="masthead-admin">
        <?php if ($isAdmin): ?>
          <a class="btn ghost sm" href="/admin/articles">Espace admin</a>
          <a class="btn sm" style="background:var(--accent);color:#fff;border-color:var(--accent);" href="/auth/logout.php">Déconnexion</a>
        <?php else: ?>
          <a class="btn ghost sm" href="/admin/login">Connexion</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="masthead-title">
      <h1>Iran &ndash; Israël Info</h1>
      <p class="tagline">Analyses · Géopolitique · Actualités en continu</p>
    </div>
  </div>
  <div class="masthead-rule"></div>
</header>

<!-- ══════════ NAV CATÉGORIES ══════════ -->
<nav class="cat-nav" aria-label="Catégories">
  <div class="cat-nav-inner">
    <?php
      $allUrl = '/accueil' . (!empty($baseFilters) ? ('?' . http_build_query($baseFilters)) : '');
    ?>
    <a class="cat-link<?= $selectedCategorySlug === '' ? ' active' : '' ?>"
       href="<?= htmlspecialchars($allUrl, ENT_QUOTES, 'UTF-8') ?>">Toutes les actualités</a>

    <?php foreach ($categories as $cat): ?>
      <?php $catUrl = category_url((string)($cat['slug'] ?? ''), $baseFilters); ?>
      <a class="cat-link<?= $selectedCategorySlug === $cat['slug'] ? ' active' : '' ?>"
         href="<?= htmlspecialchars($catUrl, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>

<!-- ══════════ BARRE DE RECHERCHE ══════════ -->
<div class="search-bar">
  <div class="search-bar-inner">
    <?php $searchAction = $selectedCategorySlug !== '' ? category_url($selectedCategorySlug) : '/accueil'; ?>
    <form method="get" action="<?= htmlspecialchars($searchAction, ENT_QUOTES, 'UTF-8') ?>" style="display:contents;">

      <div class="search-field">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="6.5" cy="6.5" r="4.5"/><path d="M10.5 10.5L14 14"/>
        </svg>
        <input type="text" name="q" placeholder="Rechercher un article, un auteur…"
               value="<?= htmlspecialchars($searchKeyword, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="date-field">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <rect x="2" y="3" width="12" height="11" rx="2"/>
          <path d="M5 1v4M11 1v4M2 7h12"/>
        </svg>
        <input type="date" name="date" value="<?= htmlspecialchars($selectedEventDate, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <button class="btn primary" type="submit">Filtrer</button>
      <?php
        $resetUrl = $selectedCategorySlug !== '' ? category_url($selectedCategorySlug) : '/accueil';
      ?>
      <a class="btn" href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>">Réinitialiser</a>
    </form>
  </div>
</div>

<!-- ══════════ CONTENU PRINCIPAL ══════════ -->
<main class="site-wrap">

  <?php if ($dbError !== ''): ?>
    <div class="db-error">Erreur base de données : <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <h2 class="section-heading">
    <?= $selectedCategoryName !== ''
        ? htmlspecialchars($selectedCategoryName, ENT_QUOTES, 'UTF-8')
        : 'À la une' ?>
    <?php if ($searchKeyword !== ''): ?>
      &mdash; Résultats pour « <?= htmlspecialchars($searchKeyword, ENT_QUOTES, 'UTF-8') ?> »
    <?php endif; ?>
  </h2>

  <?php if (count($articles) === 0 && $dbError === ''): ?>
    <div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="opacity:.3;margin-bottom:8px;">
        <path d="M4 4h16v16H4zM4 9h16M9 9v11"/>
      </svg>
      <p>Aucun article disponible pour le moment.</p>
    </div>
  <?php else: ?>
    <div class="articles-grid">
      <?php foreach ($articles as $i => $article): ?>
        <?php
          $viewUrl  = article_url($article);
          $excerpt  = article_preview_text((string)($article['contenu'] ?? ''));
          $titleHtml = article_title_safe((string)($article['titre'] ?? ''));
          $titleText = trim(html_entity_decode(strip_tags((string)($article['titre'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
          $imagePath = trim((string)($article['image_path'] ?? ''));
          if ($imagePath !== '' && !preg_match('#^https?://#i', $imagePath) && $imagePath[0] !== '/') {
              $imagePath = '/' . ltrim($imagePath, '/');
          }
          $imageAlt = $titleText !== '' ? ('Image de l\'article : ' . $titleText) : 'Illustration de l\'article';
          $auteur   = htmlspecialchars($article['auteur'] ?: 'Rédaction', ENT_QUOTES, 'UTF-8');
          $date     = htmlspecialchars((string)($article['date_evenement'] ?: $article['created_at'] ?: ''), ENT_QUOTES, 'UTF-8');

          if ($i === 0)     $cardClass = 'featured';
          elseif ($i <= 2)  $cardClass = 'secondary';
          else              $cardClass = 'small';
        ?>
        <article class="article-card <?= $cardClass ?>">
          <?php if ($cardClass === 'featured'): ?>
            <div class="card-body">
              <span class="card-label">À la une</span>
              <a class="card-title" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
                <?= $titleHtml ?>
              </a>
              <div class="card-meta">
                <span class="author"><?= $auteur ?></span>
                <span class="sep">·</span>
                <span><?= $date ?></span>
              </div>
              <?php if ($excerpt !== ''): ?>
                <p class="card-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <a class="card-read-more" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
                Lire l'article
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
              </a>
            </div>
            <div class="card-img-wrap">
              <?php if ($imagePath !== ''): ?>
                <img
                  src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8') ?>"
                  loading="lazy"
                >
              <?php else: ?>
                <div class="card-img-placeholder">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/>
                  </svg>
                </div>
              <?php endif; ?>
            </div>

          <?php elseif ($cardClass === 'secondary'): ?>
            <span class="card-label">Analyse</span>
            <a class="card-title" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
              <?= $titleHtml ?>
            </a>
            <div class="card-meta">
              <span><?= $auteur ?></span>
              <span class="sep">·</span>
              <span><?= $date ?></span>
            </div>
            <?php if ($excerpt !== ''): ?>
              <p class="card-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <a class="card-read-more" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
              Lire l'article
              <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
            </a>

          <?php else: ?>
            <span class="card-label" style="font-size:9.5px;">Actualité</span>
            <a class="card-title" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
              <?= $titleHtml ?>
            </a>
            <div class="card-meta" style="margin-top:6px;">
              <span><?= $date ?></span>
            </div>
            <a class="card-read-more" href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>">
              Lire →
            </a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- ══════════ FOOTER ══════════ -->
<footer class="site-footer">
  © <?= date('Y') ?> Iran–Israël Info &nbsp;·&nbsp; Tous droits réservés
</footer>

</body>
</html>
