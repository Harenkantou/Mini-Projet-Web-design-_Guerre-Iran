<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/article_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode non autorisee']);
    exit();
}

$imageFile = $_FILES['file'] ?? null;
if ($imageFile === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier recu']);
    exit();
}

$conn = null;

try {
    $conn = db_connect();
    $articleId = max(0, (int)($_REQUEST['article_id'] ?? 0));
    $mode = (string)($_REQUEST['mode'] ?? '');

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
    $subDir = ($mode === 'modif' && $articleId > 0)
        ? ('modif/article-' . $articleId)
        : ('editor-temp/' . date('Y-m'));
    $fileName = ($articleId > 0 ? 'article-' . $articleId : 'mce') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;

    $uploadDir = dirname(__DIR__, 2) . '/uploads/articles/' . $subDir;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Impossible de creer le dossier upload.');
    }

    $targetPath = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image.');
    }

    $publicPath = '/uploads/articles/' . $subDir . '/' . $fileName;
    $altTextInput = (string)($_REQUEST['alt_text'] ?? $_REQUEST['title'] ?? $_REQUEST['description'] ?? '');
    $altText = build_media_alt_text($altTextInput, (string)($imageFile['name'] ?? ''), $articleId);

    $hasTypeColumn = media_table_has_type_column($conn);
    $hasAltTextColumn = media_table_has_alt_text_column($conn);

    if ($hasTypeColumn && $hasAltTextColumn) {
        $typeMediaId = get_or_create_image_type_media_id($conn);
        $mediaStmt = $conn->prepare('INSERT INTO media (path, alt_text, Id_type_media) VALUES (?, ?, ?)');
        $mediaStmt->bind_param('ssi', $publicPath, $altText, $typeMediaId);
    } elseif ($hasTypeColumn) {
        $typeMediaId = get_or_create_image_type_media_id($conn);
        $mediaStmt = $conn->prepare('INSERT INTO media (path, Id_type_media) VALUES (?, ?)');
        $mediaStmt->bind_param('si', $publicPath, $typeMediaId);
    } elseif ($hasAltTextColumn) {
        $mediaStmt = $conn->prepare('INSERT INTO media (path, alt_text) VALUES (?, ?)');
        $mediaStmt->bind_param('ss', $publicPath, $altText);
    } else {
        $mediaStmt = $conn->prepare('INSERT INTO media (path) VALUES (?)');
        $mediaStmt->bind_param('s', $publicPath);
    }

    $mediaStmt->execute();
    $mediaStmt->close();

    echo json_encode(['location' => $publicPath]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
