<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/category_functions.php';

$error   = '';
$mode    = 'create';
$editCategoryId = 0;

$formData = [
    'name'        => '',
    'description' => '',
];

if (isset($_GET['edit'])) {
    $editCategoryId = max(0, (int)$_GET['edit']);
    if ($editCategoryId > 0) {
        try {
            $existing = fetch_category_by_id($editCategoryId);
            if ($existing !== null) {
                $mode     = 'edit';
                $formData = [
                    'name'        => (string)($existing['name']        ?? ''),
                    'description' => (string)($existing['description'] ?? ''),
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
                (string)($_POST['name']        ?? ''),
                (string)($_POST['description'] ?? '')
            );
            header('Location: /admin/categorie/index.php?created=1');
            exit();
        }

        if ($action === 'update') {
            $id = max(0, (int)($_POST['id'] ?? 0));
            update_category(
                $id,
                (string)($_POST['name']        ?? ''),
                (string)($_POST['description'] ?? '')
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
        $error          = $e->getMessage();
        $mode           = $action === 'update' ? 'edit' : 'create';
        $editCategoryId = max(0, (int)($_POST['id'] ?? 0));
        $formData       = [
            'name'        => (string)($_POST['name']        ?? ''),
            'description' => (string)($_POST['description'] ?? ''),
        ];
    }
}

$categories = [];
try {
    $categories = fetch_categories();
} catch (Throwable $e) {
    if ($error === '') $error = $e->getMessage();
}

$created = isset($_GET['created']) && $_GET['created'] === '1';
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';

$activePage = 'categories';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Gestion catégorie</title>
  <link rel="stylesheet" href="/assets/css/admin-articles.css">
</head>
<body>

<?php require_once __DIR__ . '/../../admin/_sidebar.php'; ?>

<div class="wrap">
  <div class="page-head">
    <h1>Gestion des catégories</h1>
    <div class="muted">CRUD des catégories dans /admin/categorie.</div>
  </div>

  <!-- FORM CARD -->
  <div class="card">
    <div class="card-title"><?= $mode === 'edit' ? 'Modifier la catégorie' : 'Ajouter une catégorie' ?></div>

    <?php if ($created): ?><div class="success">Catégorie ajoutée avec succès.</div><?php endif; ?>
    <?php if ($updated): ?><div class="success">Catégorie modifiée avec succès.</div><?php endif; ?>
    <?php if ($deleted): ?><div class="success">Catégorie supprimée avec succès.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error">Erreur&nbsp;: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <form method="post" action="/admin/categorie/index.php<?= $mode === 'edit' ? '?edit=' . (int)$editCategoryId : '' ?>">
      <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
      <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= (int)$editCategoryId ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div>
          <label for="name">Nom</label>
          <input id="name" name="name" type="text" maxlength="50" required
            value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Nom de la catégorie">
        </div>

        <div class="full">
          <label for="description">Description</label>
          <textarea id="description" name="description" style="min-height:90px;"
            placeholder="Description optionnelle…"><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" type="submit">
          <?= $mode === 'edit' ? 'Mettre à jour' : 'Ajouter' ?>
        </button>
        <?php if ($mode === 'edit'): ?>
          <a class="btn" href="/admin/categorie/index.php">Annuler</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- LIST CARD -->
  <div class="card">
    <div class="card-title">
      Liste des catégories
      <?php if (count($categories) > 0): ?>
        <span class="badge" style="margin-left:8px;"><?= count($categories) ?></span>
      <?php endif; ?>
    </div>

    <?php if (count($categories) === 0): ?>
      <p class="muted">Aucune catégorie pour le moment.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="category-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Slug</th>
              <th>Description</th>
              <th>Articles</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $category): ?>
              <tr>
                <td><?= (int)$category['Id_categorie'] ?></td>
                <td><strong><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><code style="font-size:11.5px;background:#f0f2f5;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars((string)$category['slug'], ENT_QUOTES, 'UTF-8') ?></code></td>
                <td class="muted"><?= htmlspecialchars((string)$category['description'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge"><?= (int)($category['article_count'] ?? 0) ?></span></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a class="btn" style="padding:6px 12px;font-size:12px;"
                      href="/admin/categorie/index.php?edit=<?= (int)$category['Id_categorie'] ?>">Modifier</a>
                    <form method="post" action="/admin/categorie/index.php"
                      onsubmit="return confirm('Supprimer cette catégorie ?');" style="display:inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$category['Id_categorie'] ?>">
                      <button class="btn danger" style="padding:6px 12px;font-size:12px;" type="submit">Supprimer</button>
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
