<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/TaskManager.php';
require_once 'includes/ReminderService.php';

// Nur für eingeloggte Benutzer
Auth::requireAuth();

$reminderService = new ReminderService();
$dueTasks = $reminderService->checkDueTasks();

header('Content-Type: application/json');
echo json_encode($dueTasks); 