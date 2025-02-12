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
            'oauth' => [
                'client_id' => env('DYNAMICS_OAUTH_CLIENT_ID'),
                'client_secret' => env('DYNAMICS_OAUTH_CLIENT_SECRET'),
                'redirect_uri' => env('DYNAMICS_OAUTH_REDIRECT_URI'),
                'scope' => env('DYNAMICS_OAUTH_SCOPE'),
                'grant_type' => env('DYNAMICS_OAUTH_GRANT_TYPE', 'client_credentials'),
            ],
            'page_size' => env('DYNAMICS_PAGE_SIZE', 1000),
            'options' => [
                'connect_timeout' => env('DYNAMICS_TIMEOUT', 30),
            ],
            'availability' => [
                /* The response codes that should trigger the availability check in addition to connection timeouts */
                'codes' => [502, 503, 504],

                /* The amount of failed requests before the service is marked as unavailable. */
                'threshold' => 10,

                /* The timespan in minutes in which the failed requests should occur. */
                'timespan' => 10,

                /* The cooldown in minutes after the threshold is reached. */
                'cooldown' => 2,

                /* Throw an exception that prevents calls to Dynamics when unavailable */
                'throw' => false,
            ],
        ],
    ],

];
