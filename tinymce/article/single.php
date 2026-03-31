<?php
session_start();
require_once __DIR__ . '/../db.php';

$slug         = trim((string)($_GET['slug'] ?? ''));
$articleIdParam = (int)($_GET['id'] ?? 0);
$article      = null;
$articlePhotos = [];
$categories   = [];
$dbError      = '';

if ($slug === '' && $articleIdParam <= 0) {
    http_response_code(404);
}

if ($slug !== '' || $articleIdParam > 0) {
    try {
        $conn = db_connect();

    if ($slug !== '') {
      $stmt = $conn->prepare(
        'SELECT a.Id_article, a.titre, a.contenu, a.slug, a.auteur, a.created_at, a.updated_at, a.date_evenement
         FROM article a
         WHERE a.slug = ?
         LIMIT 1'
      );
      $stmt->bind_param('s', $slug);
    } else {
      $stmt = $conn->prepare(
        'SELECT a.Id_article, a.titre, a.contenu, a.slug, a.auteur, a.created_at, a.updated_at, a.date_evenement
         FROM article a
         WHERE a.Id_article = ?
         LIMIT 1'
      );
      $stmt->bind_param('i', $articleIdParam);
    }
        $stmt->execute();
        $article = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        if ($article !== null) {
            $articleId = (int)$article['Id_article'];

            $hasAltText      = false;
            $altColumnResult = $conn->query("SHOW COLUMNS FROM media LIKE 'alt_text'");
            if ($altColumnResult !== false) {
                $hasAltText = $altColumnResult->num_rows > 0;
                $altColumnResult->free();
            }

            $altField  = $hasAltText ? 'm.alt_text' : "'' AS alt_text";
            $photosStmt = $conn->prepare(
                'SELECT m.Id_media, m.path, ' . $altField . '
                 FROM media_article ma
                 INNER JOIN media m ON m.Id_media = ma.Id_media
                 WHERE ma.Id_article = ?
                 ORDER BY m.Id_media DESC'
            );
            $photosStmt->bind_param('i', $articleId);
            $photosStmt->execute();
            $photosResult = $photosStmt->get_result();
            while ($row = $photosResult->fetch_assoc()) {
                $articlePhotos[] = $row;
            }
            $photosStmt->close();

            $catStmt = $conn->prepare(
                'SELECT c.name, c.slug
                 FROM categorie_article ca
                 INNER JOIN categorie c ON c.Id_categorie = ca.Id_categorie
                 WHERE ca.Id_article = ?
                 ORDER BY c.name ASC'
            );
            $catStmt->bind_param('i', $articleId);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            while ($row = $catResult->fetch_assoc()) {
                $categories[] = $row;
            }
            $catStmt->close();
        } else {
            http_response_code(404);
        }

        $conn->close();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
        http_response_code(500);
    }
}

function text_from_html(string $html): string
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/', ' ', $text) ?? '');
}

function absolute_url_from_path(string $path): string
{
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $scheme = $https ? 'https' : 'http';

  return $scheme . '://' . $host . $path;
}

$titleText       = $article !== null ? text_from_html((string)($article['titre'] ?? '')) : '';
$pageTitle       = $titleText !== '' ? ($titleText . ' — Iran–Israël Info') : 'Article — Iran–Israël Info';
$contentText     = $article !== null ? text_from_html((string)($article['contenu'] ?? '')) : '';
$metaDescription = $contentText !== ''
    ? (mb_strlen($contentText, 'UTF-8') > 160 ? rtrim(mb_substr($contentText, 0, 160, 'UTF-8')) . '…' : $contentText)
    : 'Consultez cet article sur Iran–Israël Info.';

$canonicalArticlePath = '/accueil';
if ($article !== null) {
  $articleSlug = trim((string)($article['slug'] ?? ''));
  $articleIdCanonical = (int)($article['Id_article'] ?? 0);
  $canonicalArticlePath = $articleSlug !== ''
    ? ('/article/' . rawurlencode($articleSlug))
    : ('/article/id/' . $articleIdCanonical);
}
$canonicalUrl = absolute_url_from_path($canonicalArticlePath);

$isAdmin    = isset($_SESSION['user']);
$activePage = 'front';
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
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="/assets/css/front.css">
</head>
<body>

<!-- ══════════ MASTHEAD ══════════ -->
<header class="masthead">
  <div class="masthead-inner">
    <div class="masthead-top">
      <span class="masthead-date"><?= (new DateTime())->format('d F Y') ?></span>
      <?php if ($isAdmin): ?>
        <div class="masthead-admin">
          <a class="btn ghost sm" href="/admin/articles">Espace admin</a>
          <a class="btn sm" style="background:var(--accent);color:#fff;border-color:var(--accent);" href="/auth/logout.php">Déconnexion</a>
        </div>
      <?php endif; ?>
    </div>
    <div class="masthead-title">
      <a href="/accueil" style="text-decoration:none;">
        <h1>Iran &ndash; Israël Info</h1>
      </a>
      <p class="tagline">Analyses · Géopolitique · Actualités en continu</p>
    </div>
  </div>
  <div class="masthead-rule"></div>
</header>

<!-- ══════════ SIDEBAR ADMIN (si connecté) ══════════ -->
<?php if ($isAdmin): ?>
  <?php require_once __DIR__ . '/../admin/_sidebar.php'; ?>
<?php endif; ?>

<!-- ══════════ ARTICLE ══════════ -->
<main class="single-wrap" <?= $isAdmin ? 'style="margin-left:220px;max-width:calc(760px + 220px);padding-left:calc(24px + 220px);"' : '' ?>>

  <a class="single-back" href="/accueil">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M13 8H3M7 4l-4 4 4 4"/></svg>
    Retour aux actualités
  </a>

  <?php if ($dbError !== ''): ?>
    <div class="db-error">Erreur base de données : <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>

  <?php elseif ($article === null): ?>
    <div style="text-align:center;padding:60px 0;">
      <p style="font-family:'DM Sans',sans-serif;font-size:15px;color:var(--ink-muted);">Article introuvable.</p>
      <a class="btn" href="/accueil" style="margin-top:16px;">Retour à l'accueil</a>
    </div>

  <?php else: ?>

    <!-- CATÉGORIES -->
    <?php if (count($categories) > 0): ?>
      <div class="single-chips">
        <?php foreach ($categories as $cat): ?>
          <a class="chip" href="/accueil/categorie/<?= rawurlencode((string)($cat['slug'] ?? '')) ?>">
            <?= htmlspecialchars((string)($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- TITRE -->
    <div class="single-title">
      <?= (string)($article['titre'] ?? 'Sans titre') ?>
    </div>

    <!-- MÉTA -->
    <div class="single-meta">
      <span class="author">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="vertical-align:middle;margin-right:4px;">
          <circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/>
        </svg>
        <?= htmlspecialchars((string)($article['auteur'] ?? 'Rédaction'), ENT_QUOTES, 'UTF-8') ?>
      </span>

      <?php if (!empty($article['date_evenement'])): ?>
        <span>
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="vertical-align:middle;margin-right:4px;">
            <rect x="2" y="3" width="12" height="11" rx="2"/><path d="M5 1v4M11 1v4M2 7h12"/>
          </svg>
          Événement : <?= htmlspecialchars((string)$article['date_evenement'], ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>

      <?php if (!empty($article['created_at'])): ?>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">
          Publié le <?= htmlspecialchars((string)$article['created_at'], ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
    </div>

    <!-- CONTENU -->
    <div class="article-body">
      <?= (string)($article['contenu'] ?? '') ?>
    </div>

    <!-- GALERIE PHOTOS -->
    <?php if (count($articlePhotos) > 0): ?>
      <div class="gallery-section">
        <h2 class="gallery-title">Photos<h2>
        <div class="photo-grid">
          <?php foreach ($articlePhotos as $photo): ?>
            <?php $alt = trim((string)($photo['alt_text'] ?? '')); ?>
            <div class="photo-item">
              <img
                src="<?= htmlspecialchars((string)($photo['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($alt !== '' ? $alt : 'Photo de l\'article', ENT_QUOTES, 'UTF-8') ?>"
                loading="lazy"
              >
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- RETOUR -->
    <div style="margin-top:48px;padding-top:24px;border-top:1px solid var(--rule);">
      <a class="single-back" href="/accueil">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M13 8H3M7 4l-4 4 4 4"/></svg>
        Retour aux actualités
      </a>
    </div>

  <?php endif; ?>
</main>

<!-- ══════════ FOOTER ══════════ -->
<footer class="site-footer" <?= $isAdmin ? 'style="margin-left:220px;"' : '' ?>>
  © <?= date('Y') ?> Iran–Israël Info &nbsp;·&nbsp; Tous droits réservés
</footer>

</body>
</html>
