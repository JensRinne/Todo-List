<?php
ob_start(); // Starte Output-Buffering
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';
require_once 'includes/TaskManager.php';
require_once 'includes/PreviewGenerator.php';

Auth::requireAuth();

$taskManager = new TaskManager();

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $taskManager->createTask($_POST, $_FILES);
                    break;
                case 'update':
                    $taskManager->updateTask($_POST['id'], $_POST, $_FILES);
                    break;
                case 'delete':
                    $taskManager->deleteTask($_POST['id']);
                    break;
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            ob_end_flush();
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Filter verarbeiten
$filter = [];
if (isset($_GET['view'])) {
    switch ($_GET['view']) {
        case 'today':
            $tasks = $taskManager->getTodayTasks();
            break;
        case 'week':
            $tasks = $taskManager->getWeekTasks();
            break;
        default:
            $tasks = $taskManager->getAllTasks();
    }
} elseif (isset($_GET['search'])) {
    $tasks = $taskManager->searchTasks($_GET['search']);
} elseif (isset($_GET['tag'])) {
    $tasks = $taskManager->getTasksByFilter(['tag' => $_GET['tag']]);
} elseif (isset($_GET['social'])) {
    $tasks = $taskManager->getTasksByFilter(['social_network' => $_GET['social']]);
} else {
    $tasks = $taskManager->getAllTasks();
}

// Am Anfang der PHP-Logik, nach dem Laden der Tasks:
if (isset($_GET['sort'])) {
    $tasks = $taskManager->sortTasks($tasks, $_GET['sort']);
} else {
    $tasks = $taskManager->sortTasks($tasks, 'created_at');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo Liste</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo-menu-container">
                <div class="logo">
                    <h1><i class="fas fa-tasks"></i> ToDo Liste</h1>
                </div>
                <div class="mobile-menu-button">
                    <button onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            <div class="search-bar">
                <form action="" method="get">
                    <input type="text" name="search" placeholder="Aufgaben suchen..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
            </div>
            <div class="nav-links" id="mobileMenu">
                <a href="index.php"><i class="fas fa-list"></i> Alle Aufgaben</a>
                <a href="?view=today"><i class="fas fa-calendar-day"></i> Heute</a>
                <a href="?view=week"><i class="fas fa-calendar-week"></i> Diese Woche</a>
                <a href="export_csv.php" class="mobile-export-button"><i class="fas fa-file-export"></i> Alle Aufgaben exportieren</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="sort-options">
            <div class="filter-group">
                <label for="sort">Sortieren nach:</label>
                <select id="sort" name="sort" onchange="window.location.href=this.value">
                    <option value="?sort=created_at" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'created_at') ? 'selected' : ''; ?>>Erstellungsdatum</option>
                    <option value="?sort=last_modified" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'last_modified') ? 'selected' : ''; ?>>Letzte Änderung</option>
                    <option value="?sort=due_date" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'due_date') ? 'selected' : ''; ?>>Fälligkeitsdatum</option>
                    <option value="?sort=priority" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'priority') ? 'selected' : ''; ?>>Priorität</option>
                    <option value="?sort=title" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'title') ? 'selected' : ''; ?>>Titel</option>
                </select>
            </div>
            <a href="export_csv.php" class="export-button desktop-export-button" title="Als CSV exportieren">
                <i class="fas fa-file-export"></i> Alle Aufgaben exportieren (CSV)
            </a>
        </div>
        <?php if (isset($_GET['tag'])): ?>
            <div class="active-filter">
                <span>Gefiltert nach Tag: #<?php echo htmlspecialchars($_GET['tag']); ?></span>
                <a href="index.php" class="reset-filter">Filter aufheben <i class="fas fa-times"></i></a>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['social'])): ?>
            <div class="active-filter">
                <span>Gefiltert nach Social Network: <i class="fab fa-<?php echo htmlspecialchars($_GET['social']); ?>"></i> <?php echo SOCIAL_NETWORKS[htmlspecialchars($_GET['social'])]; ?></span>
                <a href="index.php" class="reset-filter">Filter aufheben <i class="fas fa-times"></i></a>
            </div>
        <?php endif; ?>
        <div class="task-grid">
            <?php foreach ($tasks as $task): ?>
                <div class="task-card priority-<?php echo htmlspecialchars($task['priority']); ?>">
                    <div class="task-header">
                        <div class="task-title">
                            <?php echo htmlspecialchars($task['title']); ?>
                            <?php if ($_SESSION['username'] !== 'admin' && 
                                     ($task['created_by'] === 'Administrator' || 
                                      $task['last_modified_by'] === 'Administrator')): ?>
                                <span class="admin-badge" title="Vom Administrator bearbeitet">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="task-actions">
                            <button onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteTask('<?php echo htmlspecialchars($task['id']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="task-description">
                        <div class="description-content <?php echo strlen($task['description']) > 200 ? 'truncated' : ''; ?>">
                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                        </div>
                        <?php if (strlen($task['description']) > 200): ?>
                            <button class="toggle-description" onclick="toggleDescription(this)">
                                Mehr anzeigen <i class="fas fa-chevron-down"></i>
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($task['url'])): ?>
                            <div class="task-url">
                                <a href="<?php echo htmlspecialchars($task['url']); ?>" target="_blank">
                                    <i class="fas fa-link"></i> Website öffnen
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($task['attachments'])): ?>
                            <div class="task-attachments">
                                <?php foreach ($task['attachments'] as $attachment): ?>
                                    <div class="task-attachment">
                                        <a href="download.php?file=<?php echo htmlspecialchars($attachment['filename']); ?>" target="_blank" class="attachment-link">
                                            <?php if ($attachment['preview_type'] === 'image'): ?>
                                                <div class="attachment-preview">
                                                    <img src="<?php echo htmlspecialchars($attachment['preview']); ?>" alt="Vorschau">
                                                </div>
                                            <?php else: ?>
                                                <i class="fas <?php echo htmlspecialchars($attachment['preview']); ?>"></i>
                                            <?php endif; ?>
                                            <span class="attachment-name"><?php echo htmlspecialchars($attachment['original_name']); ?></span>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="task-meta">
                        <div><i class="fas fa-calendar"></i> Fällig: <?php echo $task['due_date'] ? date('d.m.Y', strtotime($task['due_date'])) : 'Keine Fälligkeit'; ?></div>
                        <?php if (!empty($task['social_networks'])): ?>
                            <div class="social-networks">
                                <?php foreach ($task['social_networks'] as $network): ?>
                                    <a href="?social=<?php echo urlencode($network); ?>" class="social-network">
                                        <i class="fab fa-<?php echo $network; ?>"></i> <?php echo SOCIAL_NETWORKS[$network]; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($task['tags'])): ?>
                            <div class="tags">
                                <?php foreach ($task['tags'] as $tag): ?>
                                    <a href="?tag=<?php echo urlencode($tag); ?>" class="tag">
                                        #<?php echo htmlspecialchars($tag); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="task-user-info">
                            Letzte Änderung von <?php echo htmlspecialchars($task['last_modified_by']); ?> am <?php echo date('d.m.Y', strtotime($task['last_modified_at'])); ?>@<?php echo date('H:i', strtotime($task['last_modified_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="add-task-button" onclick="showAddTaskModal()">
            <i class="fas fa-plus"></i>
        </button>

        <button class="theme-toggle" onclick="toggleTheme()" title="Theme wechseln">
            <i class="fas fa-moon"></i>
        </button>
    </div>

    <!-- Modal für neue Aufgabe -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close-button" onclick="hideTaskModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="taskForm" method="post" enctype="multipart/form-data">
                <div class="form-content">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="id" value="">
                    
                    <div class="form-group">
                        <label for="title">Titel</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Beschreibung</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="social_networks">Social Networks</label>
                        <select id="social_networks" name="social_networks[]" multiple class="select2-social-networks" style="width: 100%">
                            <?php foreach (SOCIAL_NETWORKS as $key => $name): ?>
                                <option value="<?php echo $key; ?>" data-icon="fab fa-<?php echo $key; ?>">
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="url">Website URL</label>
                        <input type="url" id="url" name="url" placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label>Dateianhänge</label>
                        <div class="file-upload-container" id="fileUploadContainer">
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" name="attachments[]" multiple class="file-input" id="attachments">
                                <div class="file-upload-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Dateien hier ablegen oder klicken zum Auswählen</p>
                                </div>
                            </div>
                            <div id="selectedFiles" class="selected-files"></div>
                            <div id="currentFiles" class="current-files" style="display: none;">
                                <h4>Aktuelle Anhänge:</h4>
                                <div id="currentFilesList"></div>
                            </div>
                        </div>
                        <small class="help-text">Erlaubte Dateitypen: PDF, PNG, JPG, TXT, DOC, DOCX, MP4, MOV, AVI, ZIP, RAR (max. 20MB)</small>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Fälligkeitsdatum</label>
                        <input type="date" id="due_date" name="due_date">
                    </div>

                    <div class="form-group">
                        <label for="priority">Priorität</label>
                        <select id="priority" name="priority">
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category">Kategorie</label>
                        <input type="text" id="category" name="category" value="allgemein">
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (mit Komma getrennt)</label>
                        <input type="text" id="tags" name="tags">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideTaskModal()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Initialisiere Benachrichtigungen
        const notifications = new TodoNotifications();
        
        // Prüfe einmal täglich auf fällige Aufgaben
        setInterval(() => {
            notifications.checkDueTasks();
        }, 24 * 60 * 60 * 1000); // 24 Stunden

        // Prüfe auch direkt beim Laden der Seite
        notifications.checkDueTasks();

        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('show');
        }

        // Select2 Initialisierung
        $(document).ready(function() {
            $('.select2-social-networks').select2({
                placeholder: 'Social Networks auswählen...',
                allowClear: true,
                templateResult: formatSocialNetwork,
                templateSelection: formatSocialNetwork
            });
        });

        // Formatierung der Social Network Optionen
        function formatSocialNetwork(state) {
            if (!state.id) {
                return state.text;
            }
            
            var icon = $(state.element).data('icon');
            return $('<span><i class="' + icon + '"></i> ' + state.text + '</span>');
        }

        function showAddTaskModal() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskForm').action.value = 'create';
            $('.select2-social-networks').val(null).trigger('change');
            document.getElementById('taskModal').style.display = 'block';
            document.body.classList.add('modal-open');
        }

        function hideTaskModal() {
            document.getElementById('taskModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        function editTask(task) {
            document.getElementById('taskForm').action.value = 'update';
            document.getElementById('taskForm').id.value = task.id;
            document.getElementById('title').value = task.title;
            document.getElementById('description').value = task.description;
            document.getElementById('url').value = task.url || '';
            document.getElementById('due_date').value = task.due_date || '';
            document.getElementById('priority').value = task.priority;
            document.getElementById('tags').value = task.tags.join(', ');
            
            // Social Networks setzen
            $('.select2-social-networks').val(task.social_networks || []).trigger('change');
            
            // Dateianhänge anzeigen
            const currentFiles = document.getElementById('currentFiles');
            const currentFilesList = document.getElementById('currentFilesList');
            if (task.attachments && task.attachments.length > 0) {
                currentFiles.style.display = 'block';
                currentFilesList.innerHTML = task.attachments.map(attachment => `
                    <div class="current-file">
                        <span><i class="fas fa-paperclip"></i> ${attachment.original_name}</span>
                        <button type="button" class="remove-file" onclick="markAttachmentForRemoval('${attachment.filename}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('');
            } else {
                currentFiles.style.display = 'none';
                currentFilesList.innerHTML = '';
            }
            
            document.getElementById('taskModal').style.display = 'block';
            document.body.classList.add('modal-open');
        }

        function markAttachmentForRemoval(filename) {
            const form = document.getElementById('taskForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'remove_attachments[]';
            input.value = filename;
            form.appendChild(input);
            
            // Entferne die Datei visuell aus der Liste
            const fileElement = event.target.closest('.current-file');
            fileElement.remove();
            
            // Verstecke den Container, wenn keine Dateien mehr vorhanden sind
            const currentFilesList = document.getElementById('currentFilesList');
            if (!currentFilesList.children.length) {
                document.getElementById('currentFiles').style.display = 'none';
            }
        }

        function deleteTask(taskId) {
            if (confirm('Möchten Sie diese Aufgabe wirklich löschen?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${taskId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Klick außerhalb des Modals schließt es
        window.onclick = function(event) {
            if (event.target == document.getElementById('taskModal')) {
                hideTaskModal();
            }
        }

        function toggleDescription(button) {
            const content = button.previousElementSibling;
            const isExpanded = content.classList.toggle('truncated');
            
            if (isExpanded) {
                button.innerHTML = 'Mehr anzeigen <i class="fas fa-chevron-down"></i>';
            } else {
                button.innerHTML = 'Weniger anzeigen <i class="fas fa-chevron-up"></i>';
            }
        }

        // Theme Management
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateThemeIcon(theme);
        }

        function toggleTheme() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.querySelector('.theme-toggle i');
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        }

        // Theme initialization
        document.addEventListener('DOMContentLoaded', () => {
            // Check for saved theme preference or system preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                setTheme('dark');
            }

            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) { // Only if no manual preference is set
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const fileUploadArea = document.getElementById('fileUploadArea');
            const attachmentsInput = document.getElementById('attachments');
            const selectedFiles = document.getElementById('selectedFiles');
            
            // Drag & Drop Funktionalität
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                fileUploadArea.classList.add('highlight');
            }

            function unhighlight(e) {
                fileUploadArea.classList.remove('highlight');
            }

            fileUploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }

            // Dateiauswahl über Button
            attachmentsInput.addEventListener('change', function(e) {
                handleFiles(this.files);
            });

            function handleFiles(files) {
                updateSelectedFilesList([...files]);
            }

            function updateSelectedFilesList(files) {
                selectedFiles.innerHTML = '';
                files.forEach((file, index) => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'selected-file';
                    fileDiv.innerHTML = `
                        <span><i class="fas fa-file"></i> ${file.name}</span>
                        <button type="button" class="remove-file" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    selectedFiles.appendChild(fileDiv);
                });

                // Event-Listener für Entfernen-Buttons
                document.querySelectorAll('.remove-file').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        const newFiles = Array.from(attachmentsInput.files).filter((_, i) => i !== index);
                        
                        // Erstelle ein neues FileList-Objekt
                        const dataTransfer = new DataTransfer();
                        newFiles.forEach(file => dataTransfer.items.add(file));
                        attachmentsInput.files = dataTransfer.files;
                        
                        updateSelectedFilesList(newFiles);
                    });
                });
            }
        });
    </script>
</body>
</html> 