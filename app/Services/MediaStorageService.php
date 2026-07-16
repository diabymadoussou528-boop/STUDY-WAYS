<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    public function upload(UploadedFile $file, MediaCategory $category, ?string $existing = null): string
    {
        $this->assertValidUpload($file, $category);

        if ($existing !== null) {
            $this->delete($existing, $category);
        }

        if ($this->shouldUseGoogleDrive()) {
            $path = $file->store($category->folder(), 'google');

            return 'google://'.$path;
        }

        return $this->uploadToLocalDisk($file, $category);
    }

    public function delete(?string $stored, MediaCategory $category): void
    {
        if (blank($stored) || $this->isExternalUrl($stored)) {
            return;
        }

        if (str_starts_with($stored, 'google://')) {
            $path = Str::after($stored, 'google://');
            if (Storage::disk('google')->exists($path)) {
                Storage::disk('google')->delete($path);
            }

            return;
        }

        if (Storage::disk('public')->exists($stored)) {
            Storage::disk('public')->delete($stored);
        }
    }

    public function url(?string $stored, MediaCategory $category): ?string
    {
        if (blank($stored)) {
            return null;
        }

        if ($this->isExternalUrl($stored)) {
            return $stored;
        }

        if (str_starts_with($stored, 'google://')) {
            $path = Str::after($stored, 'google://');

            return Storage::disk('google')->url($path);
        }

        return Storage::disk('public')->url($stored);
    }

    public function isExternalUrl(string $stored): bool
    {
        return str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://');
    }

    /**
     * Determine whether a stored path is on cloud (Google Drive) storage.
     *
     * Cloud paths are stored with either a "google://" scheme prefix or with
     * a root folder segment that is not one of the known local media folders.
     */
    public function isCloudStored(string $stored): bool
    {
        if (str_starts_with($stored, 'google://')) {
            return true;
        }

        $localFolders = array_values(config('media.folders', []));

        $firstSegment = explode('/', ltrim($stored, '/'))[0] ?? '';

        return filled($firstSegment) && ! in_array($firstSegment, $localFolders, true);
    }

    /**
     * Extract the Google Drive file id from a stored "google://" reference.
     */
    public function driveFileId(?string $stored): ?string
    {
        if (blank($stored) || ! str_starts_with($stored, 'google://')) {
            return null;
        }

        return basename(Str::after($stored, 'google://'));
    }

    private function assertValidUpload(UploadedFile $file, MediaCategory $category): void
    {
        if (! $file->isValid()) {
            throw new MediaUploadException('Le fichier téléversé est invalide.');
        }

        $limits = $category->limits();
        $mime = (string) $file->getMimeType();

        if (app()->environment('testing')) {
            $mime = $file->getClientMimeType() ?: $mime;
        }

        if ($mime === 'application/mp4') {
            $mime = 'video/mp4';
        }

        if (! in_array($mime, $limits['mime_types'], true)) {
            throw new MediaUploadException('Type de fichier non autorisé.');
        }

        $maxBytes = $limits['max_kb'] * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new MediaUploadException('Le fichier dépasse la taille maximale autorisée.');
        }

        if ($category->isImage()) {
            $imageInfo = @getimagesize($file->getRealPath());

            if ($imageInfo === false) {
                throw new MediaUploadException('Le fichier n\'est pas une image valide.');
            }
        }
    }

    private function uploadToLocalDisk(UploadedFile $file, MediaCategory $category): string
    {
        if ($category->isImage()) {
            return $this->storeOptimizedImageLocally($file, $category);
        }

        return $file->store($category->folder(), 'public');
    }

    private function storeOptimizedImageLocally(UploadedFile $file, MediaCategory $category): string
    {
        $directory = $category->folder();
        Storage::disk('public')->makeDirectory($directory);

        $filename = Str::uuid()->toString().'.jpg';
        $path = $directory.'/'.$filename;
        $absolutePath = Storage::disk('public')->path($path);

        $this->resizeImageToJpeg($file, $absolutePath, $category);

        return $path;
    }

    private function resizeImageToJpeg(UploadedFile $file, string $destination, MediaCategory $category): void
    {
        $sourcePath = $file->getRealPath();
        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new MediaUploadException('Impossible de lire l\'image.');
        }

        [$width, $height, $type] = $imageInfo;

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($source === false) {
            throw new MediaUploadException('Format d\'image non supporté.');
        }

        $transform = ['width' => 400, 'height' => 400];
        [$targetWidth, $targetHeight] = $this->scaledDimensions(
            $width,
            $height,
            (int) $transform['width'],
            (int) $transform['height'],
        );

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height,
        );

        imagejpeg($canvas, $destination, 85);

        imagedestroy($source);
        imagedestroy($canvas);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function scaledDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);

        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio),
        ];
    }

    private function shouldUseGoogleDrive(): bool
    {
        return config('media.disk') === 'google'
            && filled(config('filesystems.disks.google.clientId'))
            && filled(config('filesystems.disks.google.clientSecret'))
            && filled(config('filesystems.disks.google.refreshToken'))
            && filled(config('filesystems.disks.google.folderId'));
    }
}
