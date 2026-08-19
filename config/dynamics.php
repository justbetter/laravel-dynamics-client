<?php

declare(strict_types=1);

return [

    /* Default Dynamics connection name */
    'connection' => env('DYNAMICS_CONNECTION', 'default'),

    /* Available Dynamics connections */
    'connections' => [
        'default' => [
            /* Business Central API URL template. Every "{key}" is resolved with the parameters below. */
            'base_url' => 'https://api.businesscentral.dynamics.com/v2.0/{tenant_id}/{environment}/api/{api}/companies({company_id})',

            /* Default value for each placeholder in the URL templates. All of them can be overridden at runtime. */
            'parameters' => [
                'tenant_id' => env('DYNAMICS_TENANT_ID'),
                'environment' => env('DYNAMICS_ENVIRONMENT'),
                'api' => env('DYNAMICS_API', 'v2.0'),
                'company_id' => env('DYNAMICS_COMPANY_ID'),
            ],

            /* Map a friendly name to a company ID, e.g. 'acme' => env('DYNAMICS_COMPANY_ACME_ID') */
            'companies' => [
                //
            ],

            /* OAuth client credentials */
            'client_id' => env('DYNAMICS_OAUTH_CLIENT_ID'),
            'client_secret' => env('DYNAMICS_OAUTH_CLIENT_SECRET'),

            /* OAuth token URL template, resolved with the same parameters as the base URL */
            'token_url' => env('DYNAMICS_OAUTH_TOKEN_URL', 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token'),

            /* OAuth scope */
            'scope' => env('DYNAMICS_OAUTH_SCOPE', 'https://api.businesscentral.dynamics.com/.default'),

            /* OAuth grant type */
            'grant_type' => env('DYNAMICS_OAUTH_GRANT_TYPE', 'client_credentials'),

            /* The amount of records to request per page */
            'page_size' => (int) env('DYNAMICS_PAGE_SIZE', 1000),

            /* Specify the timeout (in seconds) for the request. */
            'timeout' => (int) env('DYNAMICS_TIMEOUT', 30),

            /* Specify the connection timeout (in seconds) for the request. */
            'connect_timeout' => (int) env('DYNAMICS_CONNECT_TIMEOUT', 10),

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
