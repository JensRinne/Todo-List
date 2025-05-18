<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/TaskManager.php';
require_once dirname(__DIR__) . '/includes/ReminderService.php';

$reminderService = new ReminderService();
$dueTasks = $reminderService->checkDueTasks();

foreach ($dueTasks as $task) {
    $reminderService->sendEmailReminder($task);
}

// Log für Debugging
$logFile = dirname(__DIR__) . '/logs/reminders.log';
$logMessage = date('Y-m-d H:i:s') . ': ' . count($dueTasks) . " Erinnerungen gesendet\n";
file_put_contents($logFile, $logMessage, FILE_APPEND); 