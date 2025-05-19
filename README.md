# ToDo Liste

Eine moderne und benutzerfreundliche ToDo-Listen-Anwendung mit Dateianhang-Funktion, Social Media Integration und Dark Mode.

## Funktionen

- Aufgabenverwaltung mit Titel, Beschreibung und Fälligkeitsdatum
- Dateianhänge mit Vorschaubildern (Bilder, PDFs, Dokumente, Videos, etc.)
- Social Media Integration für Aufgaben
- Automatische Bildvorschau (400x400 Pixel, Anzeige 150x150 Pixel)
- Responsive Design mit Mobile-First-Ansatz
- Dark Mode / Light Mode
- Benutzerauthentifizierung
- CSV-Export Funktion
- Erinnerungsfunktion per E-Mail
- Suchfunktion
- Filterung nach Datum (Heute/Diese Woche)
- Sortierung der Aufgaben

## Installation

1. Laden Sie alle Dateien in Ihr Webserver-Verzeichnis
2. Stellen Sie sicher, dass PHP 7.4 oder höher installiert ist
3. Stellen Sie sicher, dass die GD-Bibliothek für PHP aktiviert ist (für Bildverarbeitung)
4. Passen Sie die Konfigurationsdatei an (siehe Konfiguration)
5. Setzen Sie die korrekten Dateiberechtigungen:
   ```bash
   chmod 755 data/
   chmod 755 data/tasks/
   chmod 755 data/uploads/
   chmod 755 data/uploads/previews/
   ```

## Konfiguration

Die Hauptkonfiguration befindet sich in `config/config.php`. Folgende Einstellungen müssen angepasst werden:

### Wichtige Einstellungen

1. E-Mail für Benachrichtigungen:
   ```php
   define('APP_EMAIL', 'ihre.email@domain.de');
   ```

2. Basis-URL anpassen:
   ```php
   define('BASE_URL', '/ihr-unterverzeichnis');
   ```

### Optionale Einstellungen

- Erlaubte Dateitypen (`ALLOWED_FILE_TYPES`)
- Maximale Dateigröße (`MAX_FILE_SIZE`)
- Verfügbare Social Media Netzwerke (`SOCIAL_NETWORKS`)
- Zusätzliche Benutzer (`USERS`)

## Benutzer und Authentifizierung

Standardmäßig gibt es zwei Benutzerkonten:
- Administrator: Benutzername `admin`, Passwort `admin`
- Benutzer 2: Benutzername `user2`, Passwort `user2`

Es wird dringend empfohlen, diese Standardpasswörter zu ändern!

## Changelog

### Version 1.0.0
- Initiale Version mit grundlegenden ToDo-Funktionen
- Benutzerauthentifizierung
- Dateiupload-System

### Version 1.1.0
- Hinzufügung von Social Media Integration
- Verbessertes Vorschaubild-System
- Dark Mode Implementation

### Version 1.2.0
- Anpassung der Bildvorschau auf 400x400 Pixel
- Optimierung der Darstellung (150x150 Pixel im Grid)
- Mobile Ansicht optimiert (120x120 Pixel)
- Verbessertes Layout der Dateinamen

### Version 1.2.1
- Korrektur der URL in E-Mail-Benachrichtigungen für fällige Aufgaben
- Verbesserung der Linkgenerierung im ReminderService

### Version 1.2.2
- Entfernung der veralteten APP_PASSWORD Konfiguration
- Authentifizierung erfolgt nun ausschließlich über das USERS-Array

## Technische Anforderungen

- PHP 7.4 oder höher
- GD-Bibliothek für PHP
- Webserver (Apache/Nginx)
- Mindestens 20MB freier Speicherplatz
- Unterstützung für .htaccess (bei Apache)
- Cronjob-Zugriff auf dem Server

## E-Mail Benachrichtigungen

Die Anwendung verfügt über ein automatisches Benachrichtigungssystem, das Sie per E-Mail an fällige Aufgaben erinnert. Dafür ist die Einrichtung eines Cronjobs erforderlich.

### Cronjob Einrichtung

1. Erstellen Sie einen Cronjob, der täglich ausgeführt wird. Fügen Sie folgende Zeile in Ihre Crontab ein:
   ```bash
   0 9 * * * php /pfad/zu/ihrer/installation/cron/check_tasks.php
   ```
   Dies führt die Überprüfung jeden Tag um 9:00 Uhr durch.

2. Stellen Sie sicher, dass die E-Mail-Adresse in der Konfigurationsdatei korrekt eingestellt ist:
   ```php
   define('APP_EMAIL', 'ihre.email@domain.de');
   ```

### Funktionsweise

- Das System überprüft täglich alle Aufgaben auf ihr Fälligkeitsdatum
- Bei fälligen Aufgaben wird eine E-Mail an die konfigurierte Adresse gesendet
- Die E-Mail enthält:
  - Titel der Aufgabe
  - Beschreibung
  - Priorität
  - Fälligkeitsdatum
  - Link zur ToDo-Liste

### Logging

- Alle Benachrichtigungen werden in der Datei `logs/reminders.log` protokolliert
- Das Log enthält Zeitstempel und die Anzahl der gesendeten Erinnerungen
- Überprüfen Sie regelmäßig die Logdatei, um sicherzustellen, dass das Benachrichtigungssystem korrekt funktioniert

## Sicherheitshinweise

1. Ändern Sie unbedingt die Standard-Passwörter
2. Schützen Sie das Verzeichnis `data/` vor direktem Zugriff
3. Aktivieren Sie HTTPS für sichere Datenübertragung
4. Überprüfen Sie regelmäßig die Logdateien
5. Führen Sie regelmäßige Backups durch

## Bekannte Probleme

- Große Dateien können zu Timeout-Problemen führen
- Vorschaubilder werden nur für PNG und JPEG erstellt

## Support

Bei Fragen oder Problemen wenden Sie sich bitte an den Support oder erstellen Sie ein Issue im Repository.

## Lizenz

Alle Rechte vorbehalten. Diese Software darf nur mit ausdrücklicher Genehmigung verwendet werden. 

dritter test
