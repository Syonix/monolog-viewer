# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [v5.0.0](https://github.com/Syonix/monolog-viewer/releases/tag/v5.0.0) - unreleased
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v4.0.2...v5.0.0)
### New
- Decoupled: API backend and AngularJS frontend
- Moved program logic to a [separate package](https://github.com/Syonix/log-viewer-lib) for easier reuse #29
- Added support for SFTP #38
- Added copy to clipboard for extra information #34
- Added pagination for long log files
- The applications webroot is now the `web` directory for security reasons. #26

### Improved
- Changed from Flexo font to Open Sans #27 
- Implemented log file caching for subsequent requests #37
- Made application fully HTTPS ready #27
- Changed sorting to newest first (legacy behavior can still be enabled) #21

### Fixed
- Various bug fixes

## [v4.0.2](https://github.com/Syonix/monolog-viewer/releases/tag/v4.0.2) - 2015-08-11
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v4.0.1...v4.0.2)
### Fixed
- After installation an error was displayed.

## [v4.0.1](https://github.com/Syonix/monolog-viewer/releases/tag/v4.0.1) - 2015-08-07
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v4.0.0...v4.0.1)
### Fixed
- After installation a route error was displayed.

## [v4.0.0](https://github.com/Syonix/monolog-viewer/releases/tag/v4.0.0) - 2015-08-07
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v3.1.1...v4.0.0)
### New
- Fully optimized for mobile devices
- It is now possible to filter log lines by channel
- Added a full text filter, that searches date, text and context
- Added configuration option to display the log channel

## [v3.1.1](https://github.com/Syonix/monolog-viewer/releases/tag/v3.0.3) - 2015-05-10
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v3.1.0...v3.1.1)
### Improved
- Updated an external dependency

## [v3.1.0](https://github.com/Syonix/monolog-viewer/releases/tag/v3.1.0) - 2015-05-10
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v3.0.1...v3.1.0)
### New
- Added support for custom log line patterns

### Improved
- Changed document root for local files to "/", to also support files outside `DOCUMENT_ROOT`

## [v3.0.1](https://github.com/Syonix/monolog-viewer/releases/tag/v3.0.1) - 2015-05-09
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v3.0.0...v3.0.1)
### Improved
- Updated root directory for local log files to `$_SERVER['DOCUMENT_ROOT']`

## [v3.0.0](https://github.com/Syonix/monolog-viewer/releases/tag/v3.0.0) - 2015-05-09
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v2.0.0...v3.0.0)
### New
- [Flysystem](https://github.com/thephpleague/flysystem) Integration
 
### Improved
- Changed the config file format to YAML.
- Dynamic rewrite base: No need to edit the `.htaccess` file for relative root URLs.
- Various UI improvements
- Improved error handling

## [v2.0.0](https://github.com/Syonix/monolog-viewer/releases/tag/v2.0.0) - 2014-08-22
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v1.1.2...v2.0.0)
### New
- Application based on [Silex](http://silex.sensiolabs.org/)
- Added Filter for minimal log level
- Added support for relative application path
- Removed check of all log files on every page view

## [v1.1.2](https://github.com/Syonix/monolog-viewer/releases/tag/v1.1.2) - 2014-01-04
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v1.1.1...v1.1.2)
### Improved
- Updated log level colors

## [v1.1.1](https://github.com/Syonix/monolog-viewer/releases/tag/v1.1.1) - 2014-01-04
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v1.0.1...v1.1.1)
### New
- Implemented ddtraceweb/monolog-parser
- Added Tests

### Improved
- Improved htaccess

## [v1.0.1](https://github.com/Syonix/monolog-viewer/releases/tag/v1.0.1) - 2014-01-04
[See full Changelog](https://github.com/Syonix/monolog-viewer/compare/v1.0.0...v1.0.1)
### Improved
- Improved Documentation


## [v1.0.0](https://github.com/Syonix/monolog-viewer/releases/tag/v1.0.0) - 2014-01-04
### New
- Initial Release
