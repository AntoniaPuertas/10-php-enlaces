<?php

// API endpoint
if (isset($_GET['url'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    $preview = new LinkPreview($_GET['url']);
    echo json_encode($preview->getMetadata());
    exit;
}
?>

<!-- Página de ejemplo -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Link Preview</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <p>Prueba estos enlaces:</p>
    <p>
        <a href="https://www.wikipedia.org" class="preview-link">Wikipedia</a>
    </p>
    <p>
        <a href="https://www.github.com" class="preview-link">GitHub</a>
    </p>
    <p>
        <a href="https://www.musicforspinning.com" class="preview-link">Music for spinning</a>
    </p>
    <p>
        <a href="https://uiverse.io/" class="preview-link">Recursos CSS</a>
    </p>

    <script src="js/funciones2.js"></script>
</body>
</html>
