<?php
class ReminderService {
    private $taskManager;

    public function __construct() {
        $this->taskManager = new TaskManager();
    }

    public function checkDueTasks() {
        $tasks = $this->taskManager->getAllTasks();
        $dueTasks = [];
        $today = date('Y-m-d');

        foreach ($tasks as $task) {
            if (!empty($task['due_date']) && $task['due_date'] === $today) {
                $dueTasks[] = $task;
            }
        }

        return $dueTasks;
    }

    public function sendEmailReminder($task) {
        if (empty(APP_EMAIL)) {
            return false;
        }

        $to = APP_EMAIL;
        $subject = 'ToDo Erinnerung: ' . $task['title'];
        
        $message = "Hallo,\n\n";
        $message .= "die folgende Aufgabe ist heute fällig:\n\n";
        $message .= "Titel: " . $task['title'] . "\n";
        $message .= "Beschreibung: " . $task['description'] . "\n";
        $message .= "Priorität: " . $task['priority'] . "\n";
        $message .= "Fällig am: " . date('d.m.Y', strtotime($task['due_date'])) . "\n\n";
        
        // Link zur ToDo-Liste hinzufügen
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
        $todoUrl = $baseUrl . dirname($_SERVER['PHP_SELF']);
        $message .= "Öffnen Sie die ToDo-Liste für weitere Details:\n";
        $message .= $todoUrl . "/index.php\n";
        
        $headers = 'From: ToDo-Liste <noreply@' . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
} 