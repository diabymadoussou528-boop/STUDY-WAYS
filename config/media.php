<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Storage Disk
    |--------------------------------------------------------------------------
    |
    | Use "public" for local development/tests, or "cloudinary" for cloud
    | storage. Cloudinary requires CLOUDINARY_* credentials in .env.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    'cloud_prefix' => env('MEDIA_CLOUD_PREFIX', 'studways'),

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'folders' => [
        'avatar' => 'avatars',
        'course_thumbnail' => 'thumbnails',
        'course_video' => 'videos',
        'lesson_video' => 'lesson-videos',
        'lesson_resource' => 'lesson-resources',
    ],

    'limits' => [
        'avatar' => [
            'max_kb' => 5120,
            'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ],
        'course_thumbnail' => [
            'max_kb' => 5120,
            'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ],
        'course_video' => [
            'max_kb' => 512000,
            'mimes' => ['mp4', 'webm', 'ogg', 'mov'],
            'mime_types' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        ],
        'lesson_video' => [
            'max_kb' => 512000,
            'mimes' => ['mp4', 'webm', 'ogg', 'mov'],
            'mime_types' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        ],
        'lesson_resource' => [
            'max_kb' => 51200,
            'mimes' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'mp4', 'webm'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'text/plain',
                'video/mp4',
                'video/webm',
            ],
        ],
    ],

    'image_transforms' => [
        'avatar' => [
            'width' => 400,
            'height' => 400,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto',
        ],
        'course_thumbnail' => [
            'width' => 1280,
            'height' => 720,
            'crop' => 'limit',
            'quality' => 'auto',
            'fetch_format' => 'auto',
        ],
    ],

];
