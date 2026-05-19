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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | FBR Digital Invoicing — POST URL only. Bearer token comes from tbl_business_info.Sandbox_access_token.
    */
    'fbr' => [
        'post_url' => env('FBR_POST_URL', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb'),
        'statl_url' => env('FBR_STATL_URL', 'https://gw.fbr.gov.pk/dist/v1/statl'),
        'get_reg_type_url' => env('FBR_GET_REG_TYPE_URL', 'https://gw.fbr.gov.pk/dist/v1/Get_Reg_Type'),
        /** Set true in production when FBR gateway SSL is trusted. Your working cURL used verify off. */
        'verify_ssl' => env('FBR_VERIFY_SSL', false),
        'timeout' => env('FBR_HTTP_TIMEOUT', 120),
        /**
         * Max PHP script time (seconds) during post_to_fbr so default php.ini (e.g. 60) does not fatal-error
         * before Guzzle times out. Should be >= timeout + buffer.
         */
        'max_execution_time' => (int) env('FBR_MAX_EXECUTION', 210),
        /** Merged into FBR POST (e.g. ['X-Custom' => 'value']) */
        'extra_headers' => [],
    ],

];
