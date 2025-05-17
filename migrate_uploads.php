<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo Liste - Migration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Migration der Uploads</h1>
        <pre>
<?php
require_once 'config/config.php';

// Prüfe, ob das alte Upload-Verzeichnis existiert
$oldUploadsDir = APP_ROOT . '/uploads';

if (file_exists($oldUploadsDir)) {
    // Stelle sicher, dass das neue Verzeichnis existiert
    if (!file_exists(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    // Verschiebe alle Dateien aus dem alten in das neue Verzeichnis
    $files = scandir($oldUploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $oldPath = $oldUploadsDir . '/' . $file;
            $newPath = UPLOADS_DIR . '/' . $file;
            
            if (rename($oldPath, $newPath)) {
                echo "Datei {$file} erfolgreich verschoben\n";
            } else {
                echo "Fehler beim Verschieben von {$file}\n";
            }
        }
    }

    // Versuche das alte Verzeichnis zu löschen
    if (count(scandir($oldUploadsDir)) <= 2) { // Nur . und .. vorhanden
        if (rmdir($oldUploadsDir)) {
            echo "Altes Upload-Verzeichnis erfolgreich gelöscht\n";
        } else {
            echo "Fehler beim Löschen des alten Upload-Verzeichnisses\n";
        }
    }
} else {
    echo "Kein altes Upload-Verzeichnis gefunden. Migration nicht notwendig.\n";
}

echo "Migration abgeschlossen.\n";
?>
        </pre>
        <p><a href="index.php" class="btn btn-primary">Zurück zur ToDo Liste</a></p>
    </div>
</body>
</html> 