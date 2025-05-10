<?php

return [
    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'enable_client_messages' => true,
            'enable_statistics' => true,
        ],
    ],

    'app_provider' => BeyondCode\LaravelWebSockets\Apps\ConfigAppProvider::class,

    'allowed_origins' => [
        //
    ],

    'max_request_size_in_kb' => 10000,

    'path' => 'websockets',

    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
    ],

    'ssl' => [
        'local_cert' => null,
        'local_pk' => null,
        'passphrase' => null
    ],

    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],
]; 