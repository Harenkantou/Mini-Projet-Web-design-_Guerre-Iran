<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/article_functions.php';

$error = '';
$articleId = isset($_GET['id']) ? max(0, (int)$_GET['id']) : max(0, (int)($_POST['article_id'] ?? 0));
$isEdit = $articleId > 0;
$article = null;
$articleImages = [];

if ($isEdit) {
  try {
    $article = fetch_admin_article_by_id($articleId);
    if ($article === null) {
      $error = 'Article introuvable.';
      $isEdit = false;
      $articleId = 0;
    } else {
      $articleImages = fetch_article_images($articleId);
    }
  } catch (Throwable $e) {
    $error = 'Erreur serveur: ' . $e->getMessage();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titre = $_POST['titre'] ?? '';
  $titreTexte = trim(strip_tags($titre));
  $auteur = trim($_POST['auteur'] ?? '');
  $dateEvenementInput = trim($_POST['date_evenement'] ?? '');
  $contenu = $_POST['contenu'] ?? '';

  if ($titreTexte === '') {
    $error = 'Le titre est obligatoire.';
  } else {
    try {
      $dateEvenement = $dateEvenementInput !== '' ? $dateEvenementInput : null;

      if ($isEdit) {
        update_article($articleId, $titre, $contenu, $auteur, $dateEvenement);

        if (isset($_FILES['extra_images']['name']) && is_array($_FILES['extra_images']['name'])) {
          $conn = db_connect();
          $conn->begin_transaction();
          try {
            foreach ($_FILES['extra_images']['name'] as $idx => $unusedName) {
              $single = [
                'name' => $_FILES['extra_images']['name'][$idx] ?? '',
                'type' => $_FILES['extra_images']['type'][$idx] ?? '',
                'tmp_name' => $_FILES['extra_images']['tmp_name'][$idx] ?? '',
                'error' => $_FILES['extra_images']['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['extra_images']['size'][$idx] ?? 0,
              ];

              if ((int)$single['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
              }

              save_article_image($conn, $articleId, $single);
            }

            $conn->commit();
          } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
          } finally {
            $conn->close();
          }
        }

        header('Location: /admin/articles/index.php?updated=1');
        exit();
      }

      create_article($titre, $contenu, $auteur, $dateEvenement);
      header('Location: /admin/articles/index.php?created=1');
      exit();
    } catch (Throwable $e) {
      $error = 'Erreur serveur: ' . $e->getMessage();
    }
  }

  if ($isEdit) {
    $article = [
      'titre' => $titre,
      'contenu' => $contenu,
      'auteur' => $auteur,
      'date_evenement' => $dateEvenementInput,
    ];
    try {
      $articleImages = fetch_article_images($articleId);
    } catch (Throwable $e) {
      if ($error === '') {
        $error = 'Erreur serveur: ' . $e->getMessage();
      }
    }
  }
}

$pageTitle = $isEdit ? 'Admin - Modifier un article' : 'Admin - Ajouter un article';
$heading = $isEdit ? 'Modifier un article' : 'Ajouter un article';
$subtitle = $isEdit ? 'Etape modification: ajout de photos dans le sous-dossier modif.' : 'Creation avec l\'editeur TinyMCE.';
$formAction = $isEdit ? '/admin/articles/create.php?id=' . $articleId : '/admin/articles/create.php';

$defaultTitre = $article['titre'] ?? '<h2>Titre de l\'article</h2>';
$defaultAuteur = $article['auteur'] ?? '';
$defaultDate = $article['date_evenement'] ?? date('Y-m-d');
$defaultContenu = $article['contenu'] ?? '<p>Contenu de l\'article...</p>';

$imagesUploadUrl = '/admin/articles/upload_image.php';
if ($isEdit) {
  $imagesUploadUrl .= '?article_id=' . $articleId . '&mode=modif';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/css/admin-articles.css">
  <script src="/tinymce/js/tinymce/tinymce.min.js"></script>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="muted"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div class="actions">
        <a class="btn" href="/admin/articles/index.php">Retour a la liste</a>
        <a class="btn" href="/auth/logout.php">Deconnexion</a>
      </div>
    </div>

    <div class="card">
      <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="article_id" value="<?= (int)$articleId ?>">
        <?php endif; ?>

        <div class="form-grid">
          <div class="full">
            <label for="titre">Titre (TinyMCE)</label>
            <textarea id="titre" name="titre"><?= htmlspecialchars($_POST['titre'] ?? $defaultTitre, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div>
            <label for="auteur">Auteur</label>
            <input id="auteur" name="auteur" type="text" value="<?= htmlspecialchars($_POST['auteur'] ?? $defaultAuteur, ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div>
            <label for="date_evenement">Date evenement</label>
            <input id="date_evenement" name="date_evenement" type="date" value="<?= htmlspecialchars($_POST['date_evenement'] ?? $defaultDate, ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="full">
            <label for="contenu">Contenu</label>
            <div class="muted" style="margin-bottom: 8px;">
              Utilisez le bouton Image de TinyMCE pour televerser vers la base media.
            </div>
            <textarea id="contenu" name="contenu"><?= htmlspecialchars($_POST['contenu'] ?? $defaultContenu, ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <?php if ($isEdit): ?>
            <div class="full">
              <label for="extra_images">Ajouter d'autres photos</label>
              <input id="extra_images" name="extra_images[]" type="file" accept="image/*" multiple>
              <div class="muted" style="margin-top: 6px;">Les nouvelles photos seront enregistrees dans /uploads/articles/modif/article-<?= (int)$articleId ?>.</div>
            </div>

            <div class="full">
              <label>Photos existantes</label>
              <?php if (count($articleImages) === 0): ?>
                <div class="muted">Aucune photo pour cet article.</div>
              <?php else: ?>
                <div class="image-gallery">
                  <?php foreach ($articleImages as $img): ?>
                    <div class="image-item">
                      <img src="<?= htmlspecialchars((string)($img['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="Media <?= (int)($img['Id_media'] ?? 0) ?>">
                      <div class="muted compact-meta"><?= htmlspecialchars((string)($img['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="actions" style="margin-top: 14px;">
          <button class="btn primary" type="submit"><?= $isEdit ? 'Enregistrer les modifications' : 'Enregistrer l\'article' ?></button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const imageUploadUrl = <?= json_encode($imagesUploadUrl, JSON_UNESCAPED_SLASHES) ?>;

    tinymce.init({
      selector: '#titre',
      height: 170,
      menubar: false,
      plugins: 'code preview',
      toolbar: 'blocks | bold italic underline | alignleft aligncenter alignright alignjustify | code preview',
      block_formats: 'Paragraphe=p; Titre 1=h1; Titre 2=h2; Titre 3=h3; Titre 4=h4; Titre 5=h5; Titre 6=h6',
      toolbar_sticky: true,
      branding: false,
      license_key: 'gpl'
    });

    tinymce.init({
      selector: '#contenu',
      height: 420,
      menubar: 'file edit view insert format tools table help',
      plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount codesample',
      toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | outdent indent | bullist numlist | link image media table | codesample code preview visualblocks fullscreen | removeformat',
      images_upload_url: imageUploadUrl,
      images_upload_credentials: true,
      automatic_uploads: true,
      image_title: true,
      file_picker_types: 'image',
      relative_urls: false,
      remove_script_host: true,
      document_base_url: '/',
      block_formats: 'Paragraphe=p; Titre 1=h1; Titre 2=h2; Titre 3=h3; Titre 4=h4; Titre 5=h5; Titre 6=h6',
      codesample_languages: [
        { text: 'HTML/XML', value: 'markup' },
        { text: 'CSS', value: 'css' },
        { text: 'JavaScript', value: 'javascript' }
      ],
      toolbar_sticky: true,
      branding: false,
      license_key: 'gpl'
    });
  </script>
</body>
</html>
