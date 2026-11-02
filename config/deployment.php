<?php

return [
    'initial_manager' => [
        'email' => env('INITIAL_MANAGER_EMAIL'),
        'password' => env('INITIAL_MANAGER_PASSWORD'),
        'first_name' => env('INITIAL_MANAGER_FIRST_NAME'),
        'last_name' => env('INITIAL_MANAGER_LAST_NAME'),
    ],
    'reset_initial_manager_password' => (bool) env('INITIAL_MANAGER_RESET_PASSWORD', false),
];
