<?php
class PreviewGenerator {
    private $uploadsDir;
    private $previewsDir;
    private $maxWidth = 400;
    private $maxHeight = 400;

    public function __construct() {
        $this->uploadsDir = UPLOADS_DIR;
        $this->previewsDir = UPLOADS_DIR . '/previews';
        
        // Erstelle Vorschau-Verzeichnis, falls es nicht existiert
        if (!file_exists($this->previewsDir)) {
            mkdir($this->previewsDir, 0755, true);
        }
    }

    public function generatePreview($filename) {
        $filepath = $this->uploadsDir . '/' . $filename;
        $previewPath = $this->previewsDir . '/' . $filename;
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Für Bilder generiere eine Vorschau
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            // Wenn die Vorschau bereits existiert, gib den Pfad zurück
            if (file_exists($previewPath)) {
                return [
                    'type' => 'image',
                    'path' => BASE_URL . '/data/uploads/previews/' . $filename
                ];
            }
            return $this->generateImagePreview($filepath, $previewPath);
        }
        
        // Für alle anderen Dateitypen gib das entsprechende Icon zurück
        return [
            'type' => 'icon',
            'path' => $this->getFileTypeIcon($extension)
        ];
    }

    private function generateImagePreview($source, $target) {
        list($width, $height) = getimagesize($source);
        
        // Berechne neue Dimensionen
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = (int)round($width * $ratio);
        $newHeight = (int)round($height * $ratio);

        // Erstelle Thumbnail
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Aktiviere Alpha-Kanal für PNG-Bilder
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        // Lade Quellbild
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source_image = imagecreatefromjpeg($source);
                break;
            case 'png':
                $source_image = imagecreatefrompng($source);
                break;
            default:
                return null;
        }

        // Resize
        imagecopyresampled(
            $thumb, $source_image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        // Speichere Thumbnail
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, $target, 80);
                break;
            case 'png':
                imagepng($thumb, $target, 8);
                break;
        }

        imagedestroy($thumb);
        imagedestroy($source_image);

        return [
            'type' => 'image',
            'path' => BASE_URL . '/data/uploads/previews/' . basename($target)
        ];
    }

    private function getFileTypeIcon($extension) {
        // Mapping von Dateitypen zu Font Awesome Icons
        $iconMap = [
            'pdf'  => 'fa-file-pdf',
            'doc'  => 'fa-file-word',
            'docx' => 'fa-file-word',
            'txt'  => 'fa-file-alt',
            'mp4'  => 'fa-file-video',
            'mov'  => 'fa-file-video',
            'avi'  => 'fa-file-video',
            'zip'  => 'fa-file-archive',
            'rar'  => 'fa-file-archive'
        ];

        return $iconMap[$extension] ?? 'fa-file';
    }

    public function deletePreview($filename) {
        $previewPath = $this->previewsDir . '/' . $filename;
        if (file_exists($previewPath)) {
            unlink($previewPath);
        }
    }
} 