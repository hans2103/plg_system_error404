# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

Version numbering: **YY.WW.NN** (Year.Week.Number)
- YY = Last 2 digits of year (e.g., 26 for 2026)
- WW = ISO week number (e.g., 06 for week 6)
- NN = Incremental number starting at 00 for each week

## [26.06.00] - 2026-02-09 (Week 6)

### Added
- Initial release of Error 404 Custom Error Pages plugin
- Custom multilingual 404 error pages functionality
- Content protection (prevents deletion and unpublishing of configured 404 pages)
- Template integration with full layout support (header, footer, navigation)
- Language-specific configuration with fallback to wildcard
- Plugin configuration interface for easy setup
- Comprehensive README documentation
- Installation guide with template modification instructions
- Automatic update server support via GitHub
- English (en-GB) and Dutch (nl-NL) language support
- Protection events for articles and menu items:
  - onContentBeforeSave - Validates state changes
  - onContentBeforeDelete - Prevents deletion
  - onContentChangeState - Reverts unpublish attempts

### Technical Details
- Namespace: `HKweb\Plugin\System\Error404`
- Minimum Joomla version: 6.0
- Minimum PHP version: 8.3
- License: GNU GPL v3.0 or later
- Version numbering: YY.WW.NN format

### Requirements
- Template modification required (see README.md)
- Plugin must be enabled
- Menu items pointing to 404 articles must be created

[26.06.00]: https://github.com/hans2103/plg_system_error404/releases/tag/v26.06.00
