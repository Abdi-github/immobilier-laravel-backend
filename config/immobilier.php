<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    */
    'languages' => ['en', 'fr', 'de', 'it'],
    'default_language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5174'),
    'admin_url' => env('ADMIN_URL', 'http://localhost:5175'),

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_limit' => 20,
        'max_limit' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Settings
    |--------------------------------------------------------------------------
    */
    'cloudinary' => [
        'folder' => 'immobilier',
        'max_file_size' => 10 * 1024 * 1024, // 10 MB
        'max_per_batch' => 10,
        'max_per_property' => 50,
        'allowed_types' => ['jpeg', 'jpg', 'png', 'webp', 'gif'],
        'thumbnail' => [
            'width' => 400,
            'height' => 300,
            'crop' => 'fill',
        ],
        'image' => [
            'max_width' => 2000,
            'quality' => 85,
            'format' => 'webp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Providers
    |--------------------------------------------------------------------------
    */
    'translation' => [
        'primary_provider' => 'libretranslate',
        'fallback_provider' => 'deepl',

        'libretranslate' => [
            'url' => env('LIBRETRANSLATE_URL', 'http://libretranslate:5000'),
            'api_key' => env('LIBRETRANSLATE_API_KEY'),
        ],

        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'public' => [
            'max_attempts' => 100,
            'decay_minutes' => 15,
        ],
        'auth' => [
            'max_attempts' => 300,
            'decay_minutes' => 15,
        ],
        'admin' => [
            'max_attempts' => 1000,
            'decay_minutes' => 15,
        ],
        'strict' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Settings
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'token_expiry' => '7d',
        'verification_token_expiry' => 24, // hours
        'password_reset_token_expiry' => 1, // hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'default' => 'default',
        'emails' => 'emails',
        'images' => 'images',
        'translations' => 'translations',
        'search' => 'search',
    ],

];
