<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/TaskManager.php';

// Nur für eingeloggte Benutzer
Auth::requireAuth();

$taskManager = new TaskManager();
$csv = $taskManager->exportToCsv();

// Download Headers setzen
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="todo-liste_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv; 