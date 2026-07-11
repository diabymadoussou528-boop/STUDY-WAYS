<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class AvatarService
{
    public function __construct(private MediaStorageService $mediaStorage) {}

    public function store(UploadedFile $file, User $user): string
    {
        return $this->mediaStorage->upload($file, MediaCategory::Avatar, $user->avatar);
    }

    public function delete(User $user): void
    {
        $this->mediaStorage->delete($user->avatar, MediaCategory::Avatar);
        $user->update(['avatar' => null]);
    }
}
