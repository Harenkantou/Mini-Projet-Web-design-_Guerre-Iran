<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: /auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BackOffice - Editeur TinyMCE</title>
  <script src="/tinymce/js/tinymce/tinymce.min.js"></script>
</head>
<body>

<h1>BackOffice - Editeur TinyMCE</h1>
<p>Connecte en tant que: <strong><?= htmlspecialchars($_SESSION['user']['email'], ENT_QUOTES, 'UTF-8') ?></strong> | <a href="/auth/logout.php">Deconnexion</a></p>

<div id="message" style="display:none; padding: 10px; margin: 10px 0; border-radius: 5px; font-weight: bold;"></div>

<form id="myForm">
  <textarea id="myEditor" name="content">
    <p>Ecris ici ton texte...</p>
  </textarea>
</form>

<button type="button" onclick="showHTML()">Voir le HTML</button>

<h2>Code HTML genere :</h2>
<pre id="htmlOutput" style="background:#f0f0f0;padding:10px;"></pre>

<script>
  const API_URL = '/save.php';
  let saveTimeout;

  tinymce.init({
    selector: '#myEditor',
    plugins: 'lists link image',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link image',
    height: 300,
    license_key: 'gpl',
    setup: function(editor) {
      editor.on('change', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveDocument, 1000);
      });

      editor.on('remove', function() {
        saveDocument();
      });
    }
  });

  async function saveDocument() {
    try {
      const content = tinymce.get('myEditor').getContent();

      const response = await fetch(API_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: 1,
          title: 'Document',
          content: content
        })
      });

      const result = await response.json();
      if (result.success) {
        showMessage('Sauvegarde OK', 'success');
      } else {
        showMessage('Erreur: ' + result.error, 'error');
      }
    } catch (error) {
      showMessage('Erreur de sauvegarde', 'error');
      console.error('Erreur:', error);
    }
  }

  function showMessage(text, type) {
    const msgDiv = document.getElementById('message');
    msgDiv.textContent = text;
    msgDiv.style.display = 'block';

    if (type === 'success') {
      msgDiv.style.background = '#d4edda';
      msgDiv.style.color = '#155724';
      msgDiv.style.border = '1px solid #c3e6cb';
    } else {
      msgDiv.style.background = '#f8d7da';
      msgDiv.style.color = '#721c24';
      msgDiv.style.border = '1px solid #f5c6cb';
    }

    setTimeout(() => {
      msgDiv.style.display = 'none';
    }, 2000);
  }

  window.addEventListener('load', async function() {
    try {
      const response = await fetch(API_URL + '?id=1');
      const result = await response.json();

      if (result.success) {
        tinymce.get('myEditor').setContent(result.data.content);
      }
    } catch (error) {
      console.error('Erreur lors du chargement:', error);
    }
  });

  window.addEventListener('beforeunload', function() {
    saveDocument();
  });

  function showHTML() {
    const html = tinymce.get('myEditor').getContent();
    document.getElementById('htmlOutput').textContent = html;
  }
</script>

</body>
</html>
