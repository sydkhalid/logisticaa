<?php

return [
    'fleetx' => [
        'basic_auth' => env('FLEETX_BASIC_AUTH'),
        'username' => env('FLEETX_API_USERNAME'),
        'password' => env('FLEETX_API_PASSWORD'),
    ],

    'travis' => [
        'system_email' => env('TRAVIS_SYSTEM_EMAIL', 'connect@logisticaa.co.in'),
        'system_password' => env('TRAVIS_SYSTEM_PASSWORD'),
        'verify_tls' => env('TRAVIS_VERIFY_TLS', true),
        'ca_bundle' => env('TRAVIS_CA_BUNDLE'),
    ],

    'logs' => [
        'admin_emails' => env(
            'LOG_ADMIN_EMAILS',
            env('ADMIN_EMAILS', env('TRAVIS_SYSTEM_EMAIL', 'connect@logisticaa.co.in'))
        ),
    ],
];
