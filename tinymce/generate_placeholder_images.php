<?php
/**
 * Génère les images placeholder pour les articles
 */

$imagesConfig = [
    [
        'path' => 'uploads/articles/tensions-iran.jpg',
        'title' => 'Tensions Iran-Voisins',
        'color' => [220, 20, 60],
        'text_color' => [255, 255, 255]
    ],
    [
        'path' => 'uploads/articles/sanctions-economie.jpg',
        'title' => 'Sanctions Économiques',
        'color' => [184, 134, 11],
        'text_color' => [255, 255, 255]
    ],
    [
        'path' => 'uploads/articles/militaire-iran.jpg',
        'title' => 'Capacités Militaires',
        'color' => [47, 79, 79],
        'text_color' => [255, 255, 255]
    ],
    [
        'path' => 'uploads/articles/humanitaire-refugies.jpg',
        'title' => 'Crise Humanitaire',
        'color' => [220, 20, 60],
        'text_color' => [255, 255, 255]
    ],
    [
        'path' => 'uploads/articles/histoire-iran-contexte.jpg',
        'title' => 'Contexte Historique',
        'color' => [25, 25, 112],
        'text_color' => [255, 255, 255]
    ]
];

foreach ($imagesConfig as $config) {
    $filePath = __DIR__ . '/' . $config['path'];
    
    // Créer le répertoire si nécessaire
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Créer l'image que si elle n'existe pas
    if (!file_exists($filePath)) {
        createPlaceholderImage(
            $filePath,
            $config['title'],
            $config['color'],
            $config['text_color']
        );
        echo "✓ Créée: {$config['path']}\n";
    } else {
        echo "✓ Existe déjà: {$config['path']}\n";
    }
}

echo "\nToutes les images placeholder sont prêtes !\n";

function createPlaceholderImage($filePath, $title, $bgColor, $textColor) {
    // Dimensions de l'image
    $width = 800;
    $height = 450;
    
    // Créer une image
    $image = imagecreatetruecolor($width, $height);
    
    // Couleur de fond
    $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    imagefill($image, 0, 0, $bg);
    
    // Couleur du texte
    $textColorAllocated = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    
    // Ajouter du texte centré
    $font = 5; // Police par défaut
    $textBox = imagettfbbox(40, 0, $font, $title);
    
    // Calcul position centrée
    $textWidth = 280;
    $textHeight = 80;
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, 5, $x, $y, $title, $textColorAllocated);
    
    // Sauvegarder en JPEG
    imagejpeg($image, $filePath, 85);
    imagedestroy($image);
}
?>
