<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/category_functions.php';

$error = '';
$mode = 'create';
$editCategoryId = 0;

$formData = [
    'name' => '',
    'description' => '',
    'slug' => '',
];

if (isset($_GET['edit'])) {
    $editCategoryId = max(0, (int)$_GET['edit']);
    if ($editCategoryId > 0) {
        try {
            $existing = fetch_category_by_id($editCategoryId);
            if ($existing !== null) {
                $mode = 'edit';
                $formData = [
                    'name' => (string)($existing['name'] ?? ''),
                    'description' => (string)($existing['description'] ?? ''),
                    'slug' => (string)($existing['slug'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            create_category(
                (string)($_POST['name'] ?? ''),
                (string)($_POST['description'] ?? ''),
                (string)($_POST['slug'] ?? '')
            );
            header('Location: /admin/categorie/index.php?created=1');
            exit();
        }

        if ($action === 'update') {
            $id = max(0, (int)($_POST['id'] ?? 0));
            update_category(
                $id,
                (string)($_POST['name'] ?? ''),
                (string)($_POST['description'] ?? ''),
                (string)($_POST['slug'] ?? '')
            );
            header('Location: /admin/categorie/index.php?updated=1');
            exit();
        }

        if ($action === 'delete') {
            $id = max(0, (int)($_POST['id'] ?? 0));
            delete_category($id);
            header('Location: /admin/categorie/index.php?deleted=1');
            exit();
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $mode = $action === 'update' ? 'edit' : 'create';
        $editCategoryId = max(0, (int)($_POST['id'] ?? 0));
        $formData = [
            'name' => (string)($_POST['name'] ?? ''),
            'description' => (string)($_POST['description'] ?? ''),
            'slug' => (string)($_POST['slug'] ?? ''),
        ];
    }
}

$categories = [];
try {
    $categories = fetch_categories();
} catch (Throwable $e) {
    if ($error === '') {
        $error = $e->getMessage();
    }
}

$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Gestion categorie</title>
  <link rel="stylesheet" href="/assets/css/admin-articles.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>Gestion categorie</h1>
        <div class="muted">CRUD des categories dans /admin/categorie.</div>
      </div>
      <div class="actions">
        <a class="btn" href="/admin/articles/index.php">Retour aux articles</a>
        <a class="btn" href="/auth/logout.php">Deconnexion</a>
      </div>
    </div>

    <div class="card">
      <?php if ($created): ?>
        <div class="success">Categorie ajoutee avec succes.</div>
      <?php endif; ?>

      <?php if ($updated): ?>
        <div class="success">Categorie modifiee avec succes.</div>
      <?php endif; ?>

      <?php if ($deleted): ?>
        <div class="success">Categorie supprimee avec succes.</div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="error">Erreur: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <h2 style="margin-top:0;"><?= $mode === 'edit' ? 'Modifier categorie' : 'Ajouter categorie' ?></h2>
      <form method="post" action="/admin/categorie/index.php<?= $mode === 'edit' ? '?edit=' . (int)$editCategoryId : '' ?>">
        <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="id" value="<?= (int)$editCategoryId ?>">
        <?php endif; ?>

        <div class="form-grid">
          <div>
            <label for="name">Nom</label>
            <input id="name" name="name" type="text" maxlength="50" required value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div>
            <label for="slug">Slug</label>
            <input id="slug" name="slug" type="text" maxlength="50" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="full">
            <label for="description">Description</label>
            <textarea id="description" name="description" style="min-height:120px;"><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>

        <div class="actions" style="margin-top: 14px;">
          <button class="btn primary" type="submit"><?= $mode === 'edit' ? 'Mettre a jour' : 'Ajouter' ?></button>
          <?php if ($mode === 'edit'): ?>
            <a class="btn" href="/admin/categorie/index.php">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card">
      <h2 style="margin-top:0;">Liste des categories</h2>

      <?php if (count($categories) === 0): ?>
        <p>Aucune categorie pour le moment.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="category-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Articles lies</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $category): ?>
                <tr>
                  <td><?= (int)$category['Id_categorie'] ?></td>
                  <td><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)$category['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)$category['description'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (int)($category['article_count'] ?? 0) ?></td>
                  <td>
                    <div class="actions">
                      <a class="btn" href="/admin/categorie/index.php?edit=<?= (int)$category['Id_categorie'] ?>">Modifier</a>
                      <form method="post" action="/admin/categorie/index.php" onsubmit="return confirm('Supprimer cette categorie ?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$category['Id_categorie'] ?>">
                        <button class="btn" type="submit">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
