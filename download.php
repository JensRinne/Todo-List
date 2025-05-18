<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

session_start();
Auth::requireAuth();

if (!isset($_GET['file'])) {
    // HTML-Ausgabe für den Fehlerfall
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ToDo Liste - Download Fehler</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error">Datei nicht gefunden</div>
            <p><a href="index.php" class="btn btn-primary">Zurück zur ToDo Liste</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$filename = basename($_GET['file']);
$filepath = UPLOADS_DIR . '/' . $filename;

if (!file_exists($filepath)) {
    // HTML-Ausgabe für den Fehlerfall
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ToDo Liste - Download Fehler</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error">Datei nicht gefunden</div>
            <p><a href="index.php" class="btn btn-primary">Zurück zur ToDo Liste</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Finde die originale Task-Datei für den korrekten Dateinamen
$originalName = $filename; // Fallback
foreach (glob(TASKS_DIR . '/*.json') as $taskFile) {
    $task = json_decode(file_get_contents($taskFile), true);
    if (!empty($task['attachments'])) {
        foreach ($task['attachments'] as $attachment) {
            if ($attachment['filename'] === $filename) {
                $originalName = $attachment['original_name'];
                break 2;
            }
        }
    }
}

// Setze die korrekten Header für den Download
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private');
header('Pragma: private');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

readfile($filepath); 