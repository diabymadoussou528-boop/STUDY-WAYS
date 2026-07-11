<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaStorageService
{
    private ?Cloudinary $cloudinary = null;

    public function usesCloudinary(): bool
    {
        if (config('media.disk') !== 'cloudinary') {
            return false;
        }

        return filled(config('media.cloudinary.cloud_name'))
            && filled(config('media.cloudinary.api_key'))
            && filled(config('media.cloudinary.api_secret'));
    }

    public function upload(UploadedFile $file, MediaCategory $category, ?string $existing = null): string
    {
        $this->assertValidUpload($file, $category);

        if ($existing !== null) {
            $this->delete($existing, $category);
        }

        if ($this->usesCloudinary()) {
            return $this->uploadToCloudinary($file, $category);
        }

        return $this->uploadToLocalDisk($file, $category);
    }

    public function delete(?string $stored, MediaCategory $category): void
    {
        if (blank($stored) || $this->isExternalUrl($stored)) {
            return;
        }

        if ($this->isCloudStored($stored)) {
            $this->deleteFromCloudinary($stored, $category);

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

        if ($this->isCloudStored($stored)) {
            return $this->cloudinaryUrl($stored, $category);
        }

        return Storage::disk('public')->url($stored);
    }

    public function isCloudStored(string $stored): bool
    {
        $prefix = config('media.cloud_prefix', 'studways');

        return str_starts_with($stored, $prefix.'/');
    }

    public function isExternalUrl(string $stored): bool
    {
        return str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://');
    }

    public function migrateLocalToCloud(string $localPath, MediaCategory $category): ?string
    {
        if (! Storage::disk('public')->exists($localPath)) {
            return null;
        }

        if (! $this->usesCloudinary()) {
            return $localPath;
        }

        $absolutePath = Storage::disk('public')->path($localPath);

        try {
            $publicId = $this->cloudinaryPublicId($category);
            $options = [
                'public_id' => $publicId,
                'overwrite' => true,
                'resource_type' => $category->resourceType(),
            ];

            if ($category->isImage() && $transform = $category->imageTransform()) {
                $options['transformation'] = [$transform];
            }

            $result = $this->cloudinary()->uploadApi()->upload($absolutePath, $options);
            Storage::disk('public')->delete($localPath);

            return $result['public_id'] ?? $publicId;
        } catch (Throwable $exception) {
            Log::warning('Failed to migrate media to cloud.', [
                'path' => $localPath,
                'category' => $category->value,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function assertValidUpload(UploadedFile $file, MediaCategory $category): void
    {
        if (! $file->isValid()) {
            throw new MediaUploadException('Le fichier téléversé est invalide.');
        }

        $limits = $category->limits();
        $mime = (string) $file->getMimeType();

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

        $transform = $category->imageTransform() ?? ['width' => 400, 'height' => 400];
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

    private function uploadToCloudinary(UploadedFile $file, MediaCategory $category): string
    {
        try {
            $publicId = $this->cloudinaryPublicId($category);
            $options = [
                'public_id' => $publicId,
                'overwrite' => true,
                'resource_type' => $category->resourceType(),
            ];

            if ($category->isImage() && $transform = $category->imageTransform()) {
                $options['transformation'] = [$transform];
            }

            if ($category === MediaCategory::CourseVideo || $category === MediaCategory::LessonVideo) {
                $options['eager'] = [['streaming_profile' => 'hd']];
                $options['eager_async'] = true;
            }

            $result = $this->cloudinary()->uploadApi()->upload($file->getRealPath(), $options);

            return $result['public_id'] ?? $publicId;
        } catch (Throwable $exception) {
            Log::error('Cloudinary upload failed.', [
                'category' => $category->value,
                'message' => $exception->getMessage(),
            ]);

            throw new MediaUploadException('Échec du téléversement vers le stockage cloud.');
        }
    }

    private function deleteFromCloudinary(string $publicId, MediaCategory $category): void
    {
        $resourceTypes = match ($category->resourceType()) {
            'auto' => ['image', 'video', 'raw'],
            default => [$category->resourceType()],
        };

        try {
            foreach ($resourceTypes as $resourceType) {
                $result = $this->cloudinary()->uploadApi()->destroy($publicId, [
                    'resource_type' => $resourceType,
                ]);

                if (($result['result'] ?? null) === 'ok') {
                    return;
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Cloudinary delete failed.', [
                'public_id' => $publicId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function cloudinaryUrl(string $publicId, MediaCategory $category): string
    {
        $cloudinary = $this->cloudinary();

        if ($category->isImage()) {
            return (string) $cloudinary->image($publicId)->toUrl();
        }

        if ($category->resourceType() === 'video') {
            return (string) $cloudinary->video($publicId)->toUrl();
        }

        $cloudName = config('media.cloudinary.cloud_name');

        return "https://res.cloudinary.com/{$cloudName}/auto/upload/{$publicId}";
    }

    private function cloudinaryPublicId(MediaCategory $category): string
    {
        $prefix = config('media.cloud_prefix', 'studways');

        return $prefix.'/'.$category->folder().'/'.Str::uuid()->toString();
    }

    private function cloudinary(): Cloudinary
    {
        if ($this->cloudinary !== null) {
            return $this->cloudinary;
        }

        $configuration = new Configuration([
            'cloud' => [
                'cloud_name' => config('media.cloudinary.cloud_name'),
                'api_key' => config('media.cloudinary.api_key'),
                'api_secret' => config('media.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->cloudinary = new Cloudinary($configuration);

        return $this->cloudinary;
    }
}
