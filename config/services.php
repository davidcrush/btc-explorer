<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'blockstream' => [
        'base_url' => env('BLOCKSTREAM_API_BASE_URL', 'https://blockstream.info/api'),
        'timeout' => (int) env('BLOCKSTREAM_API_TIMEOUT', 10),
        'cache_store' => env('BLOCKSTREAM_CACHE_STORE', 'redis'),
        'cache_ttl' => (int) env('BLOCKSTREAM_CACHE_TTL', 90),
        'block_detail_hot_ttl' => (int) env('BLOCKSTREAM_BLOCK_DETAIL_HOT_TTL', 30),
        'block_detail_stable_ttl' => (int) env('BLOCKSTREAM_BLOCK_DETAIL_STABLE_TTL', 86400),
    ],

];
