<?php

return [

    /*
     * Blacklisted IP  addresses, ranges, countries, files and/or files of files
     *
     */

    'blacklist' => [
        // '127.0.0.1',
        // '192.168.17.0/24'
        // '127.0.0.1/255.255.255.255'
        // '10.0.0.1-10.0.0.255'
        // '172.17.*.*'
        // 'country:br'
        // 'host:google.com',
        // storage_path().DIRECTORY_SEPARATOR.'blacklisted.txt',
    ],

    /*
     * Whitelisted IP addresses, ranges, countries, files and/or files of files
     *
     */

    'whitelist' => [
        // '127.0.0.2',
        // '192.168.18.0/24'
        // '127.0.0.2/255.255.255.255'
        // '10.0.1.1-10.0.1.255'
        // '172.16.*.*'
        // 'country:ch'
        // 'host:google.com',
        // storage_path().DIRECTORY_SEPARATOR.'whitelisted.txt',
    ],

    /*
     * Response action for blocked responses
     *
     */

    'response' => [
        'code' => 403, // 200 = log && notify, but keep pages rendering

        'message' => null,

        'view' => null,

        'redirect_to' => null,

        'abort' => false, // return abort() instead of Response::make() - disabled by default
    ],

    /*
     * Do you wish to redirect non whitelisted accesses to an error page?
     *
     * You can use a route name (coming.soon) or url (/coming/soon);
     *
     */

    'redirect_non_whitelisted_to' => null,

    /*
     * How long should we keep IP addresses in cache?
     *
     */

    'cache_expire_time' => 0, // minutes - disabled by default

    /*
     *--------------------------------------------------------------------------
     * How long should we keep lists of IP addresses in cache?
     *--------------------------------------------------------------------------
     *
     */

    'ip_list_cache_expire_time' => 0, // minutes - disabled by default

    /*
     * Send suspicious events to log?
     *
     */

    'enable_log' => true,

    /*
     * Search by range allow you to store ranges of addresses in
     * your black and whitelist:
     *
     *   192.168.17.0/24 or
     *   127.0.0.1/255.255.255.255 or
     *   10.0.0.1-10.0.0.255 or
     *   172.17.*.*
     *
     * Note that range searches may be slow and waste memory, this is why
     * it is disabled by default.
     *
     */

    'enable_range_search' => true,

    /*
     * Search by country range allow you to store country ids in your
     * your black and whitelist:
     *
     *   php artisan firewall:whitelist country:us
     *   php artisan firewall:blacklist country:cn
     *
     */

    'enable_country_search' => false,

    /*
     * Should Firewall use the database?
     */

    'use_database' => false,

    /*
     * Models
     *
     * When using the "eloquent" driver, we need to know which Eloquent models
     * should be used.
     *
     */

    'firewall_model' => 'PragmaRX\Firewall\Vendor\Laravel\Models\Firewall',

    /*
     * Session object binding in the IoC Container
     *
     * When blacklisting IPs for the current session, Firewall
     * will need to instantiate the session object.
     *
     */

    'session_binding' => 'session',

    /*
     * GeoIp2 database path.
     *
     * To get a fresh version of this file, use the command
     *
     *      php artisan firewall:updategeoip
     *
     */

    'geoip_database_path' => storage_path('geoip'),

    /*
     * Block suspicious attacks
     */

    'attack_blocker' => [

        'enabled' => [
            'ip' => true,

            'country' => false,
        ],

        'cache_key_prefix' => 'firewall-attack-blocker',

        'allowed_frequency' => [

            'ip' => [
                'requests' => 50,

                'seconds' => 1 * 60, // 1 minute
            ],

            'country' => [
                'requests' => 3000,

                'seconds' => 2 * 60, // 2 minutes
            ],

        ],

        'action' => [

            'ip' => [
                'blacklist_unknown' => true,

                'blacklist_whitelisted' => false,
            ],

            'country' => [
                'blacklist_unknown' => false,

                'blacklist_whitelisted' => false,
            ],

        ],

        'response' => [
            'code' => 403, // 200 = log && notify, but keep pages rendering

            'message' => null,

            'view' => null,

            'redirect_to' => null,

            'abort' => false, // return abort() instead of Response::make() - disabled by default
        ],

    ],

    'notifications' => [
        'enabled' => true,

        'message' => [
            'title' => 'User agent',

            'message' => "A possible attack on '%s' has been detected from %s",

            'request_count' => [
                'title' => 'Request count',

                'message' => 'Received %s requests in the last %s seconds. Timestamp of first request: %s',
            ],

            'uri' => [
                'title' => 'First URI offended',
            ],

            'blacklisted' => [
                'title' => 'Was it blacklisted?',
            ],

            'user_agent' => [
                'title' => 'User agent',
            ],

            'geolocation' => [
                'title' => 'Geolocation',

                'field_latitude' => 'Latitude',

                'field_longitude' => 'Longitude',

                'field_country_code' => 'Country code',

                'field_country_name' => 'Country name',

                'field_city' => 'City',
            ],
        ],

        'route' => '',

        'from' => [
            'name' => 'Laravel Firewall',

            'address' => 'firewall@mydomain.com',

            'icon_emoji' => ':fire:',
        ],

        'users' => [
            'model' => PragmaRX\Firewall\Vendor\Laravel\Models\User::class,

            'emails' => [
                'admin@mydomain.com',
            ],
        ],

        'channels' => [
            'slack' => [
                'enabled' => true,
                'sender'  => PragmaRX\Firewall\Notifications\Channels\Slack::class,
            ],
        ],
    ],
];
