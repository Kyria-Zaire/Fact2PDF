<?php
/**
 * ImageService — Upload et traitement des images via Intervention/Image v3
 *
 * Redimensionne, convertit en WebP, stocke dans storage/uploads/.
 *
 * Usage :
 *   $svc  = new ImageService();
 *   $path = $svc->uploadLogo($_FILES['logo']); // retourne chemin public
 */

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use RuntimeException;

class ImageService
{
    private ImageManager $manager;
    private string       $uploadDir;
    private int          $maxSize;   // bytes
    private array        $allowed;   // MIME types

    public function __construct()
    {
        // Driver GD (disponible sans extension supplémentaire)
        $this->manager = new ImageManager(new Driver());

        $config          = require ROOT_PATH . '/config/app.php';
        $this->uploadDir = $config['upload']['path'];
        $this->maxSize   = $config['upload']['max_size'];
        $this->allowed   = $config['upload']['allowed'];
    }

    /**
     * Traite et stocke un logo client.
     *
     * - Valide MIME type + taille
     * - Redimensionne à max 300×150 (ratio conservé)
     * - Convertit en WebP pour optimiser le poids
     * - Génère un nom aléatoire (évite collisions et traversal)
     *
     * @param array $file  $_FILES['logo']
     * @param string $subdir Sous-dossier dans uploads/ (ex: 'logos')
     * @return string Chemin public (ex: /storage/uploads/logos/abc123.webp)
     *
     * @throws RuntimeException Si validation échoue
     */
    public function uploadLogo(array $file, string $subdir = 'logos'): string
    {
        $this->validate($file);

        $targetDir = rtrim($this->uploadDir, '/') . '/' . $subdir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Nom sécurisé aléatoire (évite toute injection via nom de fichier)
        $filename = bin2hex(random_bytes(12)) . '.webp';
        $destPath = $targetDir . '/' . $filename;

        // Traitement via Intervention/Image
        $image = $this->manager->read($file['tmp_name']);

        // Redimensionner à max 300×150 (ne pas agrandir si plus petit)
        $image->scaleDown(width: 300, height: 150);

        // Encoder en WebP (qualité 85 %) et sauvegarder
        $image->toWebp(quality: 85)->save($destPath);

        return '/storage/uploads/' . $subdir . '/' . $filename;
    }

    /**
     * Supprime une image précédente si elle existe.
     *
     * @param string $publicPath Chemin public (ex: /storage/uploads/logos/xxx.webp)
     */
    public function delete(string $publicPath): void
    {
        // Convertir chemin public → chemin absolu
        $absPath = ROOT_PATH . '/public' . $publicPath;

        if (file_exists($absPath) && is_file($absPath)) {
            // Vérification sécurité : doit être dans le dossier uploads
            $realPath    = realpath($absPath);
            $realUpload  = realpath(ROOT_PATH . '/public/storage/uploads');

            if ($realPath && $realUpload && str_starts_with($realPath, $realUpload)) {
                unlink($realPath);
            }
        }
    }

    // ---- Validation ----

    /**
     * Valide MIME type, taille, et absence d'erreur d'upload.
     *
     * @throws RuntimeException
     */
    private function validate(array $file): void
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors de l\'upload : code ' . ($file['error'] ?? 'inconnu'));
        }

        if ($file['size'] > $this->maxSize) {
            $mb = round($this->maxSize / 1048576, 1);
            throw new RuntimeException("Fichier trop volumineux (max {$mb} Mo).");
        }

        // Vérifier le vrai MIME (pas celui déclaré par le client)
        $realMime = mime_content_type($file['tmp_name']);
        if (!in_array($realMime, $this->allowed, true)) {
            throw new RuntimeException("Type de fichier non autorisé ({$realMime}).");
        }

        // Vérifier que c'est bien une image (protection contre faux MIME)
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Le fichier n\'est pas une image valide.');
        }
    }
}
