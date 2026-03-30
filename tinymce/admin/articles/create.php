<?php

require_once __DIR__ . '/../../auth/require_auth.php';
require_once __DIR__ . '/article_functions.php';

$error = '';

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
            $dateEvenement = null;
            if ($dateEvenementInput !== '') {
                $dateEvenement = $dateEvenementInput;
            }

            create_article($titre, $contenu, $auteur, $dateEvenement);
            header('Location: /admin/articles/index.php?created=1');
            exit();
        } catch (Throwable $e) {
            $error = 'Erreur serveur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Ajouter un article</title>
  <link rel="stylesheet" href="/assets/css/admin-articles.css">
  <script src="/tinymce/js/tinymce/tinymce.min.js"></script>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>Ajouter un article</h1>
        <div class="muted">Creation avec l'editeur TinyMCE.</div>
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

      <form method="post" action="/admin/articles/create.php">
        <div class="form-grid">
          <div class="full">
            <label for="titre">Titre (TinyMCE)</label>
            <textarea id="titre" name="titre"><?= htmlspecialchars($_POST['titre'] ?? '<h2>Titre de l\'article</h2>', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div>
            <label for="auteur">Auteur</label>
            <input id="auteur" name="auteur" type="text" value="<?= htmlspecialchars($_POST['auteur'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div>
            <label for="date_evenement">Date evenement</label>
            <input id="date_evenement" name="date_evenement" type="date" value="<?= htmlspecialchars($_POST['date_evenement'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="full">
            <label for="contenu">Contenu</label>
            <div class="muted" style="margin-bottom: 8px;">
              Utilisez le bouton Image de TinyMCE pour televerser vers la base media.
            </div>
            <textarea id="contenu" name="contenu"><?= htmlspecialchars($_POST['contenu'] ?? '<p>Contenu de l\'article...</p>', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
        </div>

        <div class="actions" style="margin-top: 14px;">
          <button class="btn primary" type="submit">Enregistrer l'article</button>
        </div>
      </form>
    </div>
  </div>

  <script>
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
      images_upload_url: '/admin/articles/upload_image.php',
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
