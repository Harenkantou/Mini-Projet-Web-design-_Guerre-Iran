<?php
session_start();
require_once __DIR__ . '/../db.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$article = null;
$articlePhotos = [];
$categories = [];
$dbError = '';

if ($slug === '') {
		http_response_code(404);
}

if ($slug !== '') {
		try {
				$conn = db_connect();

				$stmt = $conn->prepare(
						'SELECT a.Id_article, a.titre, a.contenu, a.slug, a.auteur, a.created_at, a.updated_at, a.date_evenement
						 FROM article a
						 WHERE a.slug = ?
						 LIMIT 1'
				);
				$stmt->bind_param('s', $slug);
				$stmt->execute();
				$article = $stmt->get_result()->fetch_assoc() ?: null;
				$stmt->close();

				if ($article !== null) {
						$articleId = (int)$article['Id_article'];

						$hasAltText = false;
						$altColumnResult = $conn->query("SHOW COLUMNS FROM media LIKE 'alt_text'");
						if ($altColumnResult !== false) {
								$hasAltText = $altColumnResult->num_rows > 0;
								$altColumnResult->free();
						}

						$altField = $hasAltText ? 'm.alt_text' : "'' AS alt_text";
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

						$categoriesStmt = $conn->prepare(
								'SELECT c.name, c.slug
								 FROM categorie_article ca
								 INNER JOIN categorie c ON c.Id_categorie = ca.Id_categorie
								 WHERE ca.Id_article = ?
								 ORDER BY c.name ASC'
						);
						$categoriesStmt->bind_param('i', $articleId);
						$categoriesStmt->execute();
						$categoriesResult = $categoriesStmt->get_result();
						while ($row = $categoriesResult->fetch_assoc()) {
								$categories[] = $row;
						}
						$categoriesStmt->close();
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
		$text = preg_replace('/\s+/', ' ', $text) ?? '';
		return trim($text);
}

$titleText = $article !== null ? text_from_html((string)($article['titre'] ?? '')) : '';
$pageTitle = $titleText !== '' ? ($titleText . ' - FrontOffice') : 'Article - FrontOffice';
$contentText = $article !== null ? text_from_html((string)($article['contenu'] ?? '')) : '';
$metaDescription = $contentText !== '' ? (mb_strlen($contentText, 'UTF-8') > 160 ? (rtrim(mb_substr($contentText, 0, 160, 'UTF-8')) . '...') : $contentText) : 'Consultez cet article.';
$isAdmin = isset($_SESSION['user']);
$activePage = 'front';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
	<meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
	<style>
		body { margin: 0; font-family: Verdana, sans-serif; background: #f5f7fb; color: #1c2940; }
		.wrap { max-width: 980px; margin: 0 auto; padding: 24px; }
		.btn { display: inline-block; text-decoration: none; padding: 10px 14px; border-radius: 8px; font-weight: 700; border: 1px solid #174ea6; color: #174ea6; background: #fff; }
		.card { margin-top: 20px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); padding: 20px; }
		.muted { color: #5b6f8f; font-size: 14px; }
		.error { color: #7a1020; background: #fde7e9; border: 1px solid #f5c2c7; border-radius: 8px; padding: 10px; margin-top: 10px; }
		.article-title { margin: 0 0 10px 0; font-size: 30px; line-height: 1.25; }
		.article-content { margin-top: 18px; line-height: 1.65; }
		.article-content img { max-width: 100%; height: auto; border-radius: 8px; }
		.chips { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; }
		.chip { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: #edf2ff; color: #2448a6; text-decoration: none; }
		.gallery { margin-top: 24px; }
		.gallery h2 { font-size: 22px; margin: 0 0 12px 0; }
		.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
		.media-item { background: #f7f9fd; border: 1px solid #e2e8f5; border-radius: 10px; overflow: hidden; }
		.media-item img { width: 100%; height: 160px; object-fit: cover; display: block; }
		.media-meta { padding: 8px 10px; font-size: 12px; color: #5b6f8f; }
	</style>
</head>
<body>
	<?php if ($isAdmin): ?>
		<?php require_once __DIR__ . '/../admin/_sidebar.php'; ?>
	<?php endif; ?>

	<div class="wrap">
		<a class="btn" href="/accueil">Retour à la liste</a>

		<div class="card">
			<?php if ($dbError !== ''): ?>
				<div class="error">Erreur base de données: <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
			<?php elseif ($article === null): ?>
				<h1 class="article-title">Article introuvable</h1>
				<p class="muted">Le slug demandé n'existe pas.</p>
			<?php else: ?>
				<h1 class="article-title"><?= (string)($article['titre'] ?? 'Sans titre') ?></h1>
				<p class="muted">
					Auteur: <?= htmlspecialchars((string)($article['auteur'] ?? 'Inconnu'), ENT_QUOTES, 'UTF-8') ?>
					| Date événement: <?= htmlspecialchars((string)($article['date_evenement'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
					| Créé le: <?= htmlspecialchars((string)($article['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
					| Mis à jour: <?= htmlspecialchars((string)($article['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
				</p>

				<?php if (count($categories) > 0): ?>
					<div class="chips">
						<?php foreach ($categories as $category): ?>
							<a class="chip" href="/?category=<?= urlencode((string)($category['slug'] ?? '')) ?>">
								<?= htmlspecialchars((string)($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="article-content">
					<?= (string)($article['contenu'] ?? '') ?>
				</div>

				<div class="gallery">
					<h2>Toutes les photos (<?= count($articlePhotos) ?>)</h2>
					<?php if (count($articlePhotos) === 0): ?>
						<p class="muted">Aucune photo liée à cet article.</p>
					<?php else: ?>
						<div class="grid">
							<?php foreach ($articlePhotos as $photo): ?>
								<?php $alt = trim((string)($photo['alt_text'] ?? '')); ?>
								<figure class="media-item">
									<img
										src="<?= htmlspecialchars((string)($photo['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
										alt="<?= htmlspecialchars($alt !== '' ? $alt : ('Image article ' . (int)($article['Id_article'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"
										loading="lazy"
									>
									<figcaption class="media-meta">
										<?= htmlspecialchars((string)($photo['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
									</figcaption>
								</figure>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
