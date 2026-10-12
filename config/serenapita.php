<?php

return [
    // Koneksi SIMRS. Driver "fake" memakai data contoh lokal sampai API SIMRS asli siap.
    'simrs' => [
        'driver' => env('SIMRS_DRIVER', 'fake'),          // fake | http
        'base_url' => env('SIMRS_BASE_URL', ''),
        'token' => env('SIMRS_TOKEN', ''),
        'webhook_secret' => env('SIMRS_WEBHOOK_SECRET', ''),
        'timeout' => (int) env('SIMRS_TIMEOUT', 15),
    ],

    // Interval polling live chat dalam milidetik.
    'livechat' => [
        'visitor_poll_ms' => (int) env('LIVECHAT_VISITOR_POLL_MS', 2500),
        'agent_poll_ms' => (int) env('LIVECHAT_AGENT_POLL_MS', 2000),
    ],
];
