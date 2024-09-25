# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.3] - 25-09-2024
### Changed
- Update `str_replace()` for php8.1.

## [1.2.2] - 07-08-2023
### Fixed
- Fix NULL exception for `setContent()` (added checking).

## [1.2.1] - 09-01-2023
### Changed
- Use the Core module for configuration tab.

## [1.2.0] - 07-01-2023
### Added
- Added a feature to delay the launch of scripts.
- Added feature "Exclude specific JS" for "Move to bottom" feature.
### Fixed
- Fixed issue with commented script element getting executed after being moved to bottom.
- Fixed "nodefer" attribute detecting (now not metter where it placed).
### Changed
- Renamed Observers.

## [1.1.0] - 26-06-2022
### Added
- Added the feature to skip lazy loading for images with attribute "nolazy"
- Added the feature to skip lazy loading for images that contain HTML classes specified via system config
### Fixed
- Fixed issues with cropped content during searching for scripts on a page.

## [1.0.5] - 21-06-2022
### Changed
- Removed php version requirement in composer.json

## [1.0.4] - 01-02-2022
### Fixed
- Removed arrow function in lazy load to support PHP 7.3

## [1.0.3] - 12-01-2022
### Added
- Added feature "use default html attribute loading="lazy" to all images".

## [1.0.2] - 24-09-2021
### Fixed
- Fixed possible issue on checkout with move js to page bottom enabled.

## [1.0.1] - 07-06-2021
### Changed
- Set default config for excluded controllers and paths in `config.xml`

### Fixed
- Additional checking for existing config data in `AbstractObserver.php`

## [1.0.0] - 28-05-2021
### Added
- Init main features

