<?php
// Générer les images placeholder au démarrage du conteneur

$baseDir = '/var/www/html';
$uploadDir = $baseDir . '/uploads/articles';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$images = [
    'tensions-iran.jpg' => ['Tensions Iran', [220, 20, 60]],
    'sanctions-economie.jpg' => ['Sanctions', [184, 134, 11]],
    'militaire-iran.jpg' => ['Militaire', [47, 79, 79]],
    'humanitaire-refugies.jpg' => ['Humanitaire', [220, 20, 60]],
    'histoire-iran-contexte.jpg' => ['Historique', [25, 25, 112]],
];

foreach ($images as $filename => $config) {
    $filePath = $uploadDir . '/' . $filename;
    
    if (!file_exists($filePath) && function_exists('imagecreatetruecolor')) {
        $img = imagecreatetruecolor(800, 450);
        if ($img) {
            $color = imagecolorallocate($img, $config[1][0], $config[1][1], $config[1][2]);
            imagefill($img, 0, 0, $color);
            
            $white = imagecolorallocate($img, 255, 255, 255);
            imagestring($img, 5, 300, 200, $config[0], $white);
            
            imagejpeg($img, $filePath, 85);
            imagedestroy($img);
        }
    }
}
?>
