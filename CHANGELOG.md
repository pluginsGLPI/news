# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- "Important" option on alerts: important alerts have a filled background of the alert color, the other ones a transparent tint of it

### Changed

- Alerts use the GLPI alert style, with a single "Color" option: the "Background color" and "Text color" options are removed
- White alerts have a white background with a border of the text color
- Alerts are aligned on the page grid
- Targets tab uses the GLPI list, with the native massive actions
- Pages rendered with Twig templates instead of PHP `echo`

### Fixed

- Fix alerts left in a deleted entity: declare their entity relation so it is checked and replaced on entity deletion

## [1.14.3] - 2026-11-05

### Fixed

- Login page CSS and JS URLs when the plugin is installed outside the GLPI root directory

## [1.14.2] - 2026-10-06

### Fixed

- Alert preview display in readonly mode
- CI: fix Psalm cache directory, drop the stray `glpi-project/tools` composer dependency, declare a unique composer autoloader suffix

## [1.14.1] - 2026-06-10

### Fixed

- Force closed alerts display after alert update.
- Clean display preferences and profile rights table on uninstall

## [1.14.0] - 2026-04-30

### Added

- Added support for displaying alerts on the service catalog page.

### Fixed

- Fix alert overflow on large login screens.

## [1.13.0] - 2025-09-22

### Added

- GLPI 11 compatibility

## [1.12.5] - 2025-09-19

### Fixed

- Fixed dynamic display of alert size.
- Fixed the size of the alert on the login page (`max-height` and `min-width`)

## [1.12.4] - 2024-11-08

- Update translations

## [1.12.3] - 2024-07-04

### Fixed

- Prevent an issue where alerts targets restrictions were not applied correctly.

## [1.12.2] - 2024-04-08

### Fixed

- Fix closed alerts not reappear if 'close alert' option deactivated.
- Improve alert text wrapping, preventing it from going outside the content of its parent
