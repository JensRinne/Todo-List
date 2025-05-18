<?php
class TaskManager {
    private $tasksDir;

    public function __construct() {
        $this->tasksDir = TASKS_DIR;
    }

    private function handleFileUpload($files) {
        if (empty($files['attachments'])) {
            return [];
        }

        $uploadedFiles = [];
        
        // Konvertiere einzelne Datei in Array-Format
        if (!is_array($files['attachments']['name'])) {
            $files['attachments'] = [
                'name' => [$files['attachments']['name']],
                'type' => [$files['attachments']['type']],
                'tmp_name' => [$files['attachments']['tmp_name']],
                'error' => [$files['attachments']['error']],
                'size' => [$files['attachments']['size']]
            ];
        }

        // Verarbeite jede hochgeladene Datei
        foreach ($files['attachments']['name'] as $key => $filename) {
            if ($files['attachments']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $fileInfo = pathinfo($filename);
            $extension = strtolower($fileInfo['extension']);

            // Überprüfe Dateityp
            if (!array_key_exists($extension, ALLOWED_FILE_TYPES) || 
                !in_array($files['attachments']['type'][$key], ALLOWED_FILE_TYPES)) {
                throw new Exception("Dateityp nicht erlaubt für: $filename");
            }

            // Überprüfe Dateigröße
            if ($files['attachments']['size'][$key] > MAX_FILE_SIZE) {
                throw new Exception("Datei ist zu groß (max. " . (MAX_FILE_SIZE / 1024 / 1024) . "MB): $filename");
            }

            // Generiere sicheren Dateinamen
            $newFilename = uniqid() . '.' . $extension;
            $uploadPath = UPLOADS_DIR . '/' . $newFilename;

            // Verschiebe die Datei
            if (!move_uploaded_file($files['attachments']['tmp_name'][$key], $uploadPath)) {
                throw new Exception("Fehler beim Hochladen der Datei: $filename");
            }

            $uploadedFiles[] = [
                'filename' => $newFilename,
                'original_name' => $filename,
                'type' => $files['attachments']['type'][$key],
                'size' => $files['attachments']['size'][$key]
            ];
        }

        return $uploadedFiles;
    }

    private function deleteAttachment($filename) {
        if ($filename) {
            $filepath = UPLOADS_DIR . '/' . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
                // Lösche auch die Vorschau
                $previewGenerator = new PreviewGenerator();
                $previewGenerator->deletePreview($filename);
            }
        }
    }

    public function createTask($data, $files = null) {
        $currentUser = Auth::getCurrentUser();
        
        // Tags aus dem Komma-getrennten String in ein Array umwandeln
        $tags = [];
        if (!empty($data['tags'])) {
            $tags = array_map('trim', explode(',', $data['tags']));
            $tags = array_filter($tags); // Leere Tags entfernen
        }

        $attachments = [];
        try {
            if ($files) {
                $attachments = $this->handleFileUpload($files);
                
                // Generiere Vorschauen für die Anhänge
                $previewGenerator = new PreviewGenerator();
                foreach ($attachments as &$attachment) {
                    $preview = $previewGenerator->generatePreview($attachment['filename']);
                    $attachment['preview_type'] = $preview['type'];
                    $attachment['preview'] = $preview['path'];
                }
            }
        } catch (Exception $e) {
            throw new Exception('Fehler beim Datei-Upload: ' . $e->getMessage());
        }

        $task = [
            'id' => uniqid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? '',
            'attachments' => $attachments,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $currentUser['name'],
            'last_modified_at' => date('Y-m-d H:i:s'),
            'last_modified_by' => $currentUser['name'],
            'due_date' => $data['due_date'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'social_networks' => $data['social_networks'] ?? [],
            'tags' => $tags,
            'recurring' => isset($data['recurring']) && $data['recurring'] === 'on',
            'recurring_pattern' => $data['recurring_pattern'] ?? null,
            'reminders' => $data['reminders'] ?? []
        ];

        file_put_contents(
            $this->tasksDir . '/' . $task['id'] . '.json',
            json_encode($task, JSON_PRETTY_PRINT)
        );

        return $task;
    }

    public function updateTask($id, $data, $files = null) {
        $currentUser = Auth::getCurrentUser();
        $taskFile = $this->tasksDir . '/' . $id . '.json';
        if (!file_exists($taskFile)) {
            return false;
        }

        $task = json_decode(file_get_contents($taskFile), true);

        // Tags aus dem Komma-getrennten String in ein Array umwandeln
        if (isset($data['tags']) && is_string($data['tags'])) {
            $tags = array_map('trim', explode(',', $data['tags']));
            $data['tags'] = array_filter($tags);
        }

        // Recurring-Checkbox korrekt verarbeiten
        if (isset($data['recurring'])) {
            $data['recurring'] = $data['recurring'] === 'on';
        }

        // Datei-Upload verarbeiten
        try {
            if ($files) {
                $newAttachments = $this->handleFileUpload($files);
                
                // Generiere Vorschauen für die Anhänge
                $previewGenerator = new PreviewGenerator();
                foreach ($newAttachments as &$attachment) {
                    $preview = $previewGenerator->generatePreview($attachment['filename']);
                    $attachment['preview_type'] = $preview['type'];
                    $attachment['preview'] = $preview['path'];
                }
                
                // Bestehende Anhänge beibehalten, wenn keine zu löschenden Anhänge markiert sind
                if (!empty($task['attachments']) && empty($data['remove_attachments'])) {
                    $data['attachments'] = array_merge($task['attachments'], $newAttachments);
                } else {
                    $data['attachments'] = $newAttachments;
                }
            }
        } catch (Exception $e) {
            throw new Exception('Fehler beim Datei-Upload: ' . $e->getMessage());
        }

        // Wenn Anhänge zum Löschen markiert sind
        if (!empty($data['remove_attachments']) && is_array($data['remove_attachments'])) {
            $remainingAttachments = [];
            foreach ($task['attachments'] as $attachment) {
                if (!in_array($attachment['filename'], $data['remove_attachments'])) {
                    $remainingAttachments[] = $attachment;
                } else {
                    $this->deleteAttachment($attachment['filename']);
                }
            }
            $data['attachments'] = $remainingAttachments;
        }

        $task = array_merge($task, $data);
        $task['last_modified_at'] = date('Y-m-d H:i:s');
        $task['last_modified_by'] = $currentUser['name'];

        file_put_contents($taskFile, json_encode($task, JSON_PRETTY_PRINT));
        return $task;
    }

    public function deleteTask($id) {
        $taskFile = $this->tasksDir . '/' . $id . '.json';
        if (file_exists($taskFile)) {
            $task = json_decode(file_get_contents($taskFile), true);
            // Lösche alle angehängten Dateien
            if (!empty($task['attachments'])) {
                foreach ($task['attachments'] as $attachment) {
                    $this->deleteAttachment($attachment['filename']);
                }
            }
            return unlink($taskFile);
        }
        return false;
    }

    public function getAllTasks() {
        $tasks = [];
        $files = glob($this->tasksDir . '/*.json');
        
        foreach ($files as $file) {
            $tasks[] = json_decode(file_get_contents($file), true);
        }

        // Sortiere nach Erstellungsdatum (neueste zuerst)
        usort($tasks, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $tasks;
    }

    public function getTasksByFilter($filter) {
        $tasks = $this->getAllTasks();
        $filtered = [];

        foreach ($tasks as $task) {
            $matches = true;
            
            if (isset($filter['status']) && $task['status'] !== $filter['status']) {
                $matches = false;
            }
            
            if (isset($filter['priority']) && $task['priority'] !== $filter['priority']) {
                $matches = false;
            }
            
            if (isset($filter['social_network']) && !in_array($filter['social_network'], $task['social_networks'])) {
                $matches = false;
            }
            
            if (isset($filter['tag']) && !in_array($filter['tag'], $task['tags'])) {
                $matches = false;
            }
            
            if (isset($filter['due_date'])) {
                $due = new DateTime($task['due_date']);
                $filterDate = new DateTime($filter['due_date']);
                if ($due->format('Y-m-d') !== $filterDate->format('Y-m-d')) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $filtered[] = $task;
            }
        }
        
        return $filtered;
    }

    public function getTodayTasks() {
        return $this->getTasksByFilter(['due_date' => date('Y-m-d')]);
    }

    public function getWeekTasks() {
        $tasks = $this->getAllTasks();
        $filtered = [];
        $today = new DateTime();
        $endOfWeek = new DateTime();
        $endOfWeek->modify('next sunday');

        foreach ($tasks as $task) {
            if (!empty($task['due_date'])) {
                $dueDate = new DateTime($task['due_date']);
                if ($dueDate >= $today && $dueDate <= $endOfWeek) {
                    $filtered[] = $task;
                }
            }
        }

        return $filtered;
    }

    public function searchTasks($query) {
        $tasks = $this->getAllTasks();
        $results = [];

        foreach ($tasks as $task) {
            if (stripos($task['title'], $query) !== false ||
                stripos($task['description'], $query) !== false ||
                array_search($query, $task['tags']) !== false) {
                $results[] = $task;
            }
        }

        return $results;
    }

    public function sortTasks($tasks, $sortBy = 'created_at', $direction = 'desc') {
        switch ($sortBy) {
            case 'due_date':
                // Sortiere nach Fälligkeitsdatum
                usort($tasks, function($a, $b) use ($direction) {
                    // Setze Tasks ohne Fälligkeitsdatum ans Ende
                    if (empty($a['due_date'])) return 1;
                    if (empty($b['due_date'])) return -1;
                    $result = strtotime($a['due_date']) - strtotime($b['due_date']);
                    return $direction === 'desc' ? -$result : $result;
                });
                break;

            case 'priority':
                // Sortiere nach Priorität (hoch -> mittel -> niedrig)
                $priority_order = ['high' => 1, 'medium' => 2, 'low' => 3];
                usort($tasks, function($a, $b) use ($priority_order, $direction) {
                    $result = $priority_order[$a['priority']] - $priority_order[$b['priority']];
                    return $direction === 'desc' ? -$result : $result;
                });
                break;

            case 'title':
                // Sortiere alphabetisch nach Titel
                usort($tasks, function($a, $b) use ($direction) {
                    $result = strcmp($a['title'], $b['title']);
                    return $direction === 'desc' ? -$result : $result;
                });
                break;

            case 'last_modified':
                // Sortiere nach letzter Änderung
                usort($tasks, function($a, $b) use ($direction) {
                    $result = strtotime($b['last_modified_at']) - strtotime($a['last_modified_at']);
                    return $direction === 'desc' ? $result : -$result;
                });
                break;

            case 'created_at':
            default:
                // Sortiere nach Erstellungsdatum
                usort($tasks, function($a, $b) use ($direction) {
                    $result = strtotime($b['created_at']) - strtotime($a['created_at']);
                    return $direction === 'desc' ? $result : -$result;
                });
                break;
        }
        return $tasks;
    }

    public function exportToCsv() {
        $tasks = $this->getAllTasks();
        $output = fopen('php://temp', 'r+');
        
        // UTF-8 BOM für korrekte Darstellung von Umlauten in Excel
        fputs($output, "\xEF\xBB\xBF");
        
        // CSV Header
        fputcsv($output, [
            'ID',
            'Titel',
            'Beschreibung',
            'URL',
            'Erstellt am',
            'Erstellt von',
            'Letzte Änderung',
            'Geändert von',
            'Fälligkeitsdatum',
            'Priorität',
            'Status',
            'Social Networks',
            'Tags'
        ], ';');
        
        // Daten schreiben
        foreach ($tasks as $task) {
            fputcsv($output, [
                $task['id'],
                $task['title'],
                $task['description'],
                $task['url'],
                $task['created_at'],
                $task['created_by'],
                $task['last_modified_at'],
                $task['last_modified_by'],
                $task['due_date'] ?? '',
                $task['priority'],
                $task['status'],
                implode(', ', $task['social_networks']),
                implode(', ', $task['tags'])
            ], ';');
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
} 