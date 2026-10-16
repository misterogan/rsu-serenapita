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

    // Database SIMRS Khanza untuk Dasbor manajemen (hanya dibaca).
    // Sebaiknya memakai user MySQL khusus dengan hak SELECT saja, atau server replika.
    'khanza' => [
        'host' => env('KHANZA_DB_HOST', ''),
        'port' => env('KHANZA_DB_PORT', '3306'),
        'database' => env('KHANZA_DB_DATABASE', ''),
        'username' => env('KHANZA_DB_USERNAME', ''),
        'password' => env('KHANZA_DB_PASSWORD', ''),
        'charset' => env('KHANZA_DB_CHARSET', 'utf8mb4'),
        'poli_igd' => env('KHANZA_POLI_IGD', 'IGDK'),       // kode poli IGD di tabel poliklinik
        'cache_minutes' => (int) env('KHANZA_CACHE_MINUTES', 10),
        'query_timeout' => (int) env('KHANZA_QUERY_TIMEOUT', 30), // detik per query
    ],

    // Interval polling live chat dalam milidetik.
    'livechat' => [
        'visitor_poll_ms' => (int) env('LIVECHAT_VISITOR_POLL_MS', 2500),
        'agent_poll_ms' => (int) env('LIVECHAT_AGENT_POLL_MS', 2000),
    ],
];
