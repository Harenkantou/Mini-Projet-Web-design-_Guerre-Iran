<?php

require_once __DIR__ . '/../../db.php';

function fetch_categories(): array
{
    $items = [];

    $conn = db_connect();
    $stmt = $conn->prepare(
        'SELECT c.Id_categorie, c.name, c.description, c.slug,
                COUNT(ca.Id_categorie_article) AS article_count
         FROM categorie c
         LEFT JOIN categorie_article ca ON ca.Id_categorie = c.Id_categorie
         GROUP BY c.Id_categorie, c.name, c.description, c.slug
         ORDER BY c.name ASC, c.Id_categorie DESC'
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $items;
}

function fetch_category_by_id(int $categoryId): ?array
{
    if ($categoryId <= 0) {
        return null;
    }

    $conn = db_connect();
    $stmt = $conn->prepare(
        'SELECT Id_categorie, name, description, slug
         FROM categorie
         WHERE Id_categorie = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;

    $stmt->close();
    $conn->close();

    return $row;
}

function create_category(string $name, string $description, ?string $slugInput = null): int
{
    $name = trim($name);
    $description = trim($description);
    $slug = build_category_slug($slugInput !== null && trim($slugInput) !== '' ? $slugInput : $name);

    if ($name === '') {
        throw new InvalidArgumentException('Le nom de la categorie est obligatoire.');
    }

    $conn = db_connect();
    $stmt = $conn->prepare('INSERT INTO categorie (name, description, slug) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $description, $slug);
    $stmt->execute();
    $id = (int)$conn->insert_id;

    $stmt->close();
    $conn->close();

    return $id;
}

function update_category(int $categoryId, string $name, string $description, ?string $slugInput = null): void
{
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Categorie invalide.');
    }

    $name = trim($name);
    $description = trim($description);
    $slug = build_category_slug($slugInput !== null && trim($slugInput) !== '' ? $slugInput : $name);

    if ($name === '') {
        throw new InvalidArgumentException('Le nom de la categorie est obligatoire.');
    }

    $conn = db_connect();
    $stmt = $conn->prepare('UPDATE categorie SET name = ?, description = ?, slug = ? WHERE Id_categorie = ?');
    $stmt->bind_param('sssi', $name, $description, $slug, $categoryId);
    $stmt->execute();

    $stmt->close();
    $conn->close();
}

function delete_category(int $categoryId): void
{
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Categorie invalide.');
    }

    $conn = db_connect();
    $conn->begin_transaction();

    try {
        $deleteLinks = $conn->prepare('DELETE FROM categorie_article WHERE Id_categorie = ?');
        $deleteLinks->bind_param('i', $categoryId);
        $deleteLinks->execute();
        $deleteLinks->close();

        $deleteCategory = $conn->prepare('DELETE FROM categorie WHERE Id_categorie = ?');
        $deleteCategory->bind_param('i', $categoryId);
        $deleteCategory->execute();
        $deleteCategory->close();

        $conn->commit();
        $conn->close();
    } catch (Throwable $e) {
        $conn->rollback();
        $conn->close();
        throw $e;
    }
}

function build_category_slug(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(strtolower($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string)$text, '-');

    if ($text === '') {
        return 'categorie';
    }

    return $text;
}
