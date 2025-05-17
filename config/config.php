<?php
define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('TASKS_DIR', DATA_DIR . '/tasks');
define('UPLOADS_DIR', DATA_DIR . '/uploads');
define('APP_PASSWORD', password_hash('admin', PASSWORD_DEFAULT)); // Bitte ändern Sie das Passwort!
define('APP_EMAIL', 'E-mail@adresse'); // Ihre E-Mail-Adresse für Benachrichtigungen
define('BASE_URL', '/pfad/zum/script'); // Basispfad für URLs

// Erlaubte Dateitypen für Upload
define('ALLOWED_FILE_TYPES', [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'txt'  => 'text/plain',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'mp4'  => 'video/mp4',
    'mov'  => 'video/quicktime',
    'avi'  => 'video/x-msvideo',
    'zip'  => 'application/zip',
    'rar'  => 'application/x-rar-compressed'
]);
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB

// Social Networks Definition
define('SOCIAL_NETWORKS', [
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'twitter' => 'Twitter',
    'linkedin' => 'LinkedIn',
    'tiktok' => 'TikTok',
    'youtube' => 'YouTube',
    'pinterest' => 'Pinterest'
]);

// Benutzer Definition
define('USERS', [
    'admin' => [
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'name' => 'Administrator'
    ],
    'user2' => [
        'password' => password_hash('user2', PASSWORD_DEFAULT),
        'name' => 'Benutzer 2'
    ]
]);

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Verzeichnisse erstellen, falls sie nicht existieren
$directories = [DATA_DIR, TASKS_DIR, UPLOADS_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Fehlerberichterstattung
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', DATA_DIR . '/error.log'); 