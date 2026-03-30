<?php

require_once __DIR__ . '/../../db.php';

function fetch_admin_articles(int $limit = 50): array
{
    $articles = [];

    $conn = db_connect();
    $stmt = $conn->prepare(
          'SELECT a.Id_article, a.titre, a.contenu, a.auteur, a.date_evenement, a.created_at, a.updated_at, m.path AS image_path
            FROM article a
            LEFT JOIN (
                SELECT Id_article, MIN(Id_media) AS first_media_id
                FROM media_article
                GROUP BY Id_article
            ) am ON am.Id_article = a.Id_article
            LEFT JOIN media m ON m.Id_media = am.first_media_id
            ORDER BY COALESCE(a.updated_at, a.created_at) DESC
            LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $articles;
}

function create_article(string $titre, string $contenu, string $auteur, ?string $dateEvenement): int
{
    $conn = db_connect();
    $conn->begin_transaction();

    try {
        $rawContenu = $contenu;
        $contenu = remove_images_from_content($contenu);

        $slug = build_slug($titre);
        $stmt = $conn->prepare(
            'INSERT INTO article (titre, contenu, slug, auteur, created_at, updated_at, date_evenement)
             VALUES (?, ?, ?, ?, NOW(), NOW(), ?)'
        );
        $stmt->bind_param('sssss', $titre, $contenu, $slug, $auteur, $dateEvenement);
        $stmt->execute();

        $id = (int)$conn->insert_id;
        $stmt->close();

        link_article_images_from_content($conn, $id, $rawContenu);

        $conn->commit();
        $conn->close();

        return $id;
    } catch (Throwable $e) {
        $conn->rollback();
        $conn->close();
        throw $e;
    }
}

function remove_images_from_content(string $html): string
{
    if (trim($html) === '') {
        return $html;
    }

    // Remove image tags from article HTML so media is tracked only via media/media_article.
    $html = preg_replace('/<img\b[^>]*>/i', '', $html);

    if ($html === null) {
        return '';
    }

    return trim($html);
}

function link_article_images_from_content(mysqli $conn, int $articleId, string $contenu): void
{
    if ($articleId <= 0 || trim($contenu) === '') {
        return;
    }

    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $contenu, $matches);
    $srcs = $matches[1] ?? [];

    if (!$srcs) {
        return;
    }

    $paths = [];
    foreach ($srcs as $src) {
        $path = normalize_media_path_from_src((string)$src);
        if ($path === null) {
            continue;
        }

        $paths[$path] = true;
    }

    if (!$paths) {
        return;
    }

    $mediaSelect = $conn->prepare('SELECT Id_media FROM media WHERE path = ? LIMIT 1');
    $linkCheck = $conn->prepare('SELECT 1 FROM media_article WHERE Id_media = ? AND Id_article = ? LIMIT 1');
    $linkInsert = $conn->prepare('INSERT INTO media_article (Id_media, Id_article) VALUES (?, ?)');

    foreach (array_keys($paths) as $path) {
        $mediaSelect->bind_param('s', $path);
        $mediaSelect->execute();
        $mediaResult = $mediaSelect->get_result();
        $mediaRow = $mediaResult->fetch_assoc();

        if (!$mediaRow) {
            continue;
        }

        $mediaId = (int)$mediaRow['Id_media'];

        $linkCheck->bind_param('ii', $mediaId, $articleId);
        $linkCheck->execute();
        $exists = $linkCheck->get_result()->fetch_assoc();

        if ($exists) {
            continue;
        }

        $linkInsert->bind_param('ii', $mediaId, $articleId);
        $linkInsert->execute();
    }

    $mediaSelect->close();
    $linkCheck->close();
    $linkInsert->close();
}

function normalize_media_path_from_src(string $src): ?string
{
    $path = parse_url($src, PHP_URL_PATH);
    if (!is_string($path) || trim($path) === '') {
        return null;
    }

    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^(\./|\.\./)+#', '', $path);

    if ($path === null) {
        return null;
    }

    if (!str_starts_with($path, '/')) {
        $path = '/' . ltrim($path, '/');
    }

    if (!str_starts_with($path, '/uploads/articles/')) {
        return null;
    }

    return $path;
}

function build_slug(string $title): string
{
    $plainTitle = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $slug = trim(strtolower($plainTitle));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');

    if ($slug === '') {
        $slug = 'article';
    }

    return $slug . '-' . date('YmdHis');
}

function save_article_image(mysqli $conn, int $articleId, array $imageFile): void
{
    if (($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload image invalide.');
    }

    $tmpPath = (string)($imageFile['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Fichier image non valide.');
    }

    $maxSize = 5 * 1024 * 1024;
    $size = (int)($imageFile['size'] ?? 0);
    if ($size <= 0 || $size > $maxSize) {
        throw new RuntimeException('Image trop lourde (max 5 Mo).');
    }

    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $imageInfo = @getimagesize($tmpPath);
    $mime = $imageInfo['mime'] ?? '';
    if (!isset($allowedMimeToExt[$mime])) {
        throw new RuntimeException('Format image non supporte (jpg, png, webp, gif).');
    }

    $ext = $allowedMimeToExt[$mime];
    $fileName = 'article-' . $articleId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;

    $uploadDir = dirname(__DIR__, 2) . '/uploads/articles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Impossible de creer le dossier upload.');
    }

    $targetPath = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image.');
    }

    $publicPath = '/uploads/articles/' . $fileName;
    $typeMediaId = get_or_create_image_type_media_id($conn);

    $mediaStmt = $conn->prepare('INSERT INTO media (path, Id_type_media) VALUES (?, ?)');
    $mediaStmt->bind_param('si', $publicPath, $typeMediaId);
    $mediaStmt->execute();
    $mediaId = (int)$conn->insert_id;
    $mediaStmt->close();

    $linkStmt = $conn->prepare('INSERT INTO media_article (Id_media, Id_article) VALUES (?, ?)');
    $linkStmt->bind_param('ii', $mediaId, $articleId);
    $linkStmt->execute();
    $linkStmt->close();
}

function get_or_create_image_type_media_id(mysqli $conn): int
{
    $label = 'image';

    $select = $conn->prepare('SELECT Id_type_media FROM type_media WHERE libelle = ? LIMIT 1');
    $select->bind_param('s', $label);
    $select->execute();
    $result = $select->get_result();
    $row = $result->fetch_assoc();
    $select->close();

    if ($row) {
        return (int)$row['Id_type_media'];
    }

    $insert = $conn->prepare('INSERT INTO type_media (libelle) VALUES (?)');
    $insert->bind_param('s', $label);
    $insert->execute();
    $id = (int)$conn->insert_id;
    $insert->close();

    return $id;
}
