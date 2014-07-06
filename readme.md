# Firewall

[![Latest Stable Version](https://poser.pugx.org/pragmarx/firewall/v/stable.png)](https://packagist.org/packages/pragmarx/firewall) [![License](https://poser.pugx.org/pragmarx/firewall/license.png)](https://packagist.org/packages/pragmarx/firewall)

#### A Laravel 4 package to help you block IP addresses from accessing your application or just some routes

### Usage

This package provides two route filters:

`'fw-block-bl'`: to block all blacklisted IP addresses to access filtered routes

`'fw-allow-wl'`: to allow all whitelisted IP addresses to access filtered routes

So, for instance, you could have a blocking group and put all your routes inside it:

```
Route::group(['before' => 'fw-block-bl'], function()
{
    Route::get('/', 'HomeController@index');
});
```

Or you could use both. In the following example the allow group will give free access to the 'coming soon' page and block or just redirect non-whitelisted IP addresses to another, while still blocking access to the blacklisted ones.

```
Route::group(['before' => 'fw-block-bl'], function()
{
    Route::get('coming/soon', function()
    {
        return "We are about to launch, please come back in a few days.";
    });

    Route::group(['before' => 'fw-allow-wl'], function()
    {
        Route::get('/', 'HomeController@index');
    });
});
```

Non-whitelisted IP addresses can be blocked or redirected. To configure redirection you'll have to publish the  `config.php` file and configure:

```
'redirect_non_whitelisted_to' => 'coming/soon',
```

To blacklist or whitelist IP addresses, use the artisan commands:

```
firewall
  firewall:blacklist          Add an IP address to blacklist.
  firewall:clear              Remove all ip addresses from white and black lists.
  firewall:list               List all IP address, white and blacklisted.
  firewall:remove             Remove an IP address from white or black list.
  firewall:whitelist          Add an IP address to whitelist.
```

This is a result from `firewall:list`:

```
+--------------+-----------+-----------+
| IP Address   | Whitelist | Blacklist |
+--------------+-----------+-----------+
| 10.17.12.7   |           |     X     |
| 10.17.12.100 |     X     |           |
| 10.17.12.101 |     X     |           |
| 10.17.12.102 |     X     |           |
| 10.17.12.200 |           |     X     |
+--------------+-----------+-----------+
```

You can also use the `Firewall Facade` to manage the lists:

```
$ip = '10.17.12.1';

$whitelisted = Firewall::isWhitelisted($ip);
$blacklisted = Firewall::isBlacklisted($ip);

Firewall::whitelist($ip);
Firewall::blacklist($ip, true); /// true = force in case IP is whitelisted

if (Firewall::whichList($ip))  // returns false, 'whitelist' or 'blacklist'
{
    Firewall::remove($ip);
}
```

Return a blocking access response:

```
return Firewall::blockAccess();
```

Suspicious events will be (if you wish) logged, so `tail` it:

```
php artisan tail
```

### Blocking Whole Countries

You can block a country by, instead of an ip address, pass `country:<2-letter ISO code>`  

```
php artisan firewall:blacklist country:uk
```

You will have to add this requirement to your `composer.json` file:

```
"geoip/geoip": "~1.14"
```

You can find all of the codes here: [isocodes](http://www.spoonfork.org/isocodes.html)

### Installation

#### Requirements

- Laravel 4.1+

#### Installing

First, you need to be sure you have a Composer that supports PSR-4, so execute

```
composer self-update
```

or

```
sudo composer self-update
```

Require the Firewall package:

```
composer require "pragmarx/firewall":"0.2.*"
```

Create the migration:

```
php artisan firewall:tables
```

Migrate it

```
php artisan migrate
```

Add the service provider to your app/config/app.php:

```
'PragmaRX\Firewall\Vendor\Laravel\ServiceProvider',
```

To publish the configuration file you'll have to:

```
artisan config:publish pragmarx/firewall
```

### TODO

- Tests, tests, tests.

### Author

[Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro) 

### License

Firewall is licensed under the MIT License - see the `LICENSE` file for details

### Contributing

Pull requests and issues are more than welcome.
