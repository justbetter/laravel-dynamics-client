<?php

use JustBetter\DynamicsClient\OData\Pages\Customer;

return [

    /* Resource Configuration */
    'resources' => [
        Customer::class => 'CustomerCard',
    ],

    /* Default Dynamics Connection Name */
    'connection' => env('DYNAMICS_CONNECTION', 'default'),

    /* Available Dynamics Connections */
    'connections' => [
        'default' => [
            'base_url' => env('DYNAMICS_BASE_URL'),
            'version' => env('DYNAMICS_VERSION', 'ODataV4'),
            'company' => env('DYNAMICS_COMPANY'),
            'username' => env('DYNAMICS_USERNAME'),
            'password' => env('DYNAMICS_PASSWORD'),
            'auth' => env('DYNAMICS_AUTH', 'ntlm'),
            'page_size' => env('DYNAMICS_PAGE_SIZE', 1000),
            'options' => [
                'connect_timeout' => 5,
            ],
        ],
    ],

];
