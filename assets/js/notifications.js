class TodoNotifications {
    constructor() {
        this.checkPermission();
        this.notifiedTasks = new Set();
        
        // Täglich um Mitternacht den Cache leeren
        const midnight = new Date();
        midnight.setHours(24, 0, 0, 0);
        const msUntilMidnight = midnight - new Date();
        
        setTimeout(() => {
            this.notifiedTasks.clear();
            // Jeden Tag um Mitternacht wiederholen
            setInterval(() => {
                this.notifiedTasks.clear();
            }, 24 * 60 * 60 * 1000);
        }, msUntilMidnight);
    }

    async checkPermission() {
        if (!("Notification" in window)) {
            console.log("Dieser Browser unterstützt keine Benachrichtigungen");
            return;
        }

        if (Notification.permission === "default") {
            await Notification.requestPermission();
        }
    }

    async sendNotification(title, options = {}) {
        if (Notification.permission === "granted") {
            const notification = new Notification(title, {
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                ...options
            });

            notification.onclick = function() {
                window.focus();
                this.close();
            };
        }
    }

    async checkDueTasks() {
        try {
            const response = await fetch('check_due_tasks.php');
            const tasks = await response.json();

            for (const task of tasks) {
                // Prüfen ob die Aufgabe heute schon benachrichtigt wurde
                if (!this.notifiedTasks.has(task.id)) {
                    this.sendNotification(`Fällige Aufgabe: ${task.title}`, {
                        body: `Die Aufgabe "${task.title}" ist heute fällig.\nPriorität: ${task.priority}`,
                        tag: `todo-${task.id}`,
                        renotify: false
                    });
                    // Aufgabe als benachrichtigt markieren
                    this.notifiedTasks.add(task.id);
                }
            }
        } catch (error) {
            console.error('Fehler beim Prüfen der fälligen Aufgaben:', error);
        }
    }
} 