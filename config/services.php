<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // ISA accounts: AWS Cognito user pool behind auth.slacklineinternational.org
    'cognito' => [
        'host' => env('COGNITO_HOST', 'https://auth.slacklineinternational.org'),
        'client_id' => env('COGNITO_CLIENT_ID'),
        'client_secret' => env('COGNITO_CLIENT_SECRET'),
        'redirect' => env('COGNITO_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/callback'),
        'logout_uri' => env('COGNITO_LOGOUT_URI', env('APP_URL')),
    ],

    'isa' => [
        'api_url' => env('ISA_API_URL', 'https://account-api.slacklineinternational.org'),
    ],

];
