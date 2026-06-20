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

    'iae_cloud' => [
        'base_url' => env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'),
        'api_key' => env('IAE_API_KEY', 'KEY-MHS-133'),
        'nim' => env('IAE_NIM', '102022400047'),
        'team_id' => env('IAE_TEAM_ID', 'TEAM-03'),
        'exchange' => env('IAE_EXCHANGE', 'iae.central.exchange'),
    ],

];
