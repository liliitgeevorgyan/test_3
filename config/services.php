<?php

return [
    'finance' => [
        'url' => env('FINANCE_SERVICE_URL', 'http://finance-service:8080'),
        'timeout' => env('FINANCE_SERVICE_TIMEOUT', 30),
    ],
];
