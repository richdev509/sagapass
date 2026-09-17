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

    'sagaloto' => [
        'api_url' => env('SAGALOTO_API_URL'),
        'api_key' => env('SAGALOTO_API_KEY'),
    ],

    // Secret dédié à la signature HMAC des webhooks sortants vers les
    // partenaires (NotifyPartnerSessionWebhook / NotifyPartnerKycExpiryWebhook)
    // — volontairement distinct du client_secret de chaque DeveloperApplication
    // pour ne pas coupler la rotation de l'un à celle de l'autre.
    'sagaid_webhook' => [
        'secret' => env('SAGAID_WEBHOOK_SECRET'),
    ],

];
