# Change Log
All notable changes to this project will be documented in this file.

## [2.3.0] - 2019-09-11
### Added
- Laravel 6.0 support
- Support to identify IP behind Cloudflare
- Artisan command firewall:cache:clear
- Ability to set the log stack. New key in config: `'log_stack' => ['single']`

### Fixed
- Response in notification-only mode
- Migrations being execute when not in database mode
- Exception when trying to remove from database an ip inside a country

## [2.2.1] - 2018-07-31
### Fixed
- Fix whitelisted IP being blocked by AttackBlocker

## [2.0.2] - 2017-08-26
### Fixed
- Minor fixes

## [2.0.1] - 2017-08-21
### Fixed
- Minor fixes

## [2.0.0] - 2017-08-21
### Added
- Attack blocker

## [1.1.0] - 2017-08-20
### Added
- GeoIp2 database file updater artisan command

## [1.0.9] - 2017-08-13
### Added
- Laravel 5.5 autodiscovery
- Snippets to readme
### Changed
- Laravel 5.* install docs
### Fix
- Readme things
- Markdown headings

## [1.0.8] - 2017-01-31
### Updated
- To Laravel 5.4

## [1.0.7] - 2016-12-25
### Fixed
- Lexical problem

## [1.0.6] - 2016-12-24
### Updated
- Support package

## [1.0.5] - 2016-12-24
### Fixed
- Output on firewall:clear command
- Whitelisting problem

## [1.0.4] - 2016-12-24
### Added
- Ability to black/whitelist IPs for the session

## [1.0.3] - 2016-05-14
### Fixed
- Remove forgotten brace

## [1.0.2] - 2016-05-13
### Fixed
- Bug on PHP 7.0.2
- Deprecated Table helper (Symfony)

## [1.0.1] - 2016-04-18
### Changed
- Upgrade support package
### Fixed
- Middleware problem

## [1.0.0] - 2015-12-24
### Added
- Move to Middleware (BC)

## [1.0.0] - 2015-12-24
### Added
- Move to Middleware (BC)

## [0.5.4] - 2015-12-01
### Fixed
- Config publishing docs
- Minor fixes

## [0.5.3] - 2015-03
### Added
- Add support for GeoIP

## [0.5.2] - 2015-03
### Fixed
- Fix incorrectly using array as object
 
## [0.5.1] - 2014-02
### Fixed
- Minor fixes and improvements
 
## [0.5.0] - 2014-02-18
### Added
- Laravel 5 compatibility.
- Allow black and white lists to be stored in arrays, files or file of files, instead of a database table.
- Allow redirect_non_whitelisted_to to be a named route or url (to).
- Option to disable database lookup, disabled by default.
- Change log, according to http://keepachangelog.com/.
- Add cache for the list of IP addresses.

### Changed
- Removed unnecessary configuration options.
- Range search now defaults to enabled.

## [0.2.0] - 2014-07-08
### Fixed
- Migrator fixed
- Rename migrator
