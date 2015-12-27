<?php

return [

    'create_firewall_alias' => true,

    'firewall_alias' => 'Firewall',

    /**
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
        // storage_path().DIRECTORY_SEPARATOR.'blacklisted.txt',
    ],

    /**
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
        // storage_path().DIRECTORY_SEPARATOR.'whitelisted.txt',
    ],

    /**
     * Code and message for blocked responses
     *
     */

    'block_response_code' => 403,

    'block_response_message' => null,

    'block_response_abort' => false, // return abort() instead of Response::make() - disabled by default

    /**
     * Do you wish to redirect non whitelisted accesses to an error page?
     *
     * You can use a route name (coming.soon) or url (/coming/soon);
     *
     */

    'redirect_non_whitelisted_to' => null,

    /**
     * How long should we keep IP addresses in cache?
     *
     */

    'cache_expire_time' => 0, // minutes - disabled by default

    /**
     *--------------------------------------------------------------------------
     * How long should we keep lists of IP addresses in cache?
     *--------------------------------------------------------------------------
     *
     */

    'ip_list_cache_expire_time' => 0, // minutes - disabled by default

    /**
     * Send suspicious events to log?
     *
     */

    'enable_log' => true,

    /**
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

    /**
     * Search by country range allow you to store country ids in your
     * your black and whitelist:
     *
     *   php artisan firewall:whitelist country:us
     *   php artisan firewall:blacklist country:cn
     *
     */

    'enable_country_search' => false,

    /**
     * Should Firewall use the database?
     */

    'use_database' => false,

    /**
     * Models
     *
     * When using the "eloquent" driver, we need to know which Eloquent models
     * should be used.
     *
     */

    'firewall_model' => 'PragmaRX\Firewall\Vendor\Laravel\Models\Firewall',

];
