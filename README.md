# Joomla System Plugin: Custom 404 Error Pages

A Joomla 6.x system plugin that allows you to display custom, multilingual 404 error pages while protecting them from accidental deletion or unpublishing.

## Features

- **Custom 404 Pages**: Display beautifully designed error pages instead of the default Joomla error
- **Multilingual Support**: Configure different 404 pages for each language
- **Template Integration**: Uses your site's template layout (header, footer, navigation)
- **Content Protection**: Prevents configured 404 articles and menu items from being deleted or unpublished
- **Easy Configuration**: Simple plugin settings with language and menu item selection
- **SEO Friendly**: Proper HTTP 404 status codes maintained
- **No Template Dependency**: Works with any Joomla template (requires minor template modification)

## Requirements

- **Joomla**: 6.0+
- **PHP**: 8.3+
- **Template Modification**: Required (see installation instructions below)

## Installation

### 1. Install the Plugin

1. Download the latest release from [GitHub Releases](https://github.com/hans2103/plg_system_error404/releases)
2. In Joomla Admin, go to **System → Extensions → Install**
3. Upload and install the plugin package
4. Go to **System → Plugins**
5. Find "**Error 404 - Custom Error Pages**" and click to enable it

### 2. Configure the Plugin

1. In the plugin settings, click **404 Error Pages**
2. Click **Add Item** to add a language-specific 404 page
3. Configure each item:
   - **Language**: Select a language (e.g., `en-GB`) or `*` for all languages
   - **Menu Item**: Select a menu item that links to your custom 404 article
4. **Save & Close**

### 3. Modify Your Template (REQUIRED)

The plugin requires a small modification to your template's layout file to display custom 404 content.

#### Find Your Template's Main Layout File

The main layout file is typically located at:
```
/templates/YOUR_TEMPLATE/index.php
```

Or sometimes in:
```
/templates/YOUR_TEMPLATE/html/layouts/layout/main.php
```

#### Make the Modification

Locate the line that includes the component output:
```php
<jdoc:include type="component"/>
```

Replace it with:
```php
<?php if (isset($GLOBALS['error_page_component_output'])) : ?>
    <?php echo $GLOBALS['error_page_component_output']; ?>
<?php else : ?>
    <jdoc:include type="component"/>
<?php endif; ?>
```

**Example (before):**
```php
<main id="main" class="main">
    <div class="container">
        <jdoc:include type="component"/>
    </div>
</main>
```

**Example (after):**
```php
<main id="main" class="main">
    <div class="container">
        <?php if (isset($GLOBALS['error_page_component_output'])) : ?>
            <?php echo $GLOBALS['error_page_component_output']; ?>
        <?php else : ?>
            <jdoc:include type="component"/>
        <?php endif; ?>
    </div>
</main>
```

#### Alternative: Modify error.php (Optional)

If you prefer to keep the logic in `error.php`, you can add the check there instead. This approach gives you more control over the error page rendering.

### 4. Create Your Custom 404 Articles

1. Create articles for your 404 error pages (one per language if multilingual)
2. Add engaging content, helpful links, search functionality, etc.
3. Create menu items pointing to these articles (can be hidden menu items)
4. Configure these menu items in the plugin settings

## How It Works

### Architecture

```
Non-existent URL
    ↓
Joomla throws RouteNotFoundException (404)
    ↓
error.php rendered by Joomla
    ↓
Plugin checks if custom 404 page is configured
    ↓
Plugin prepares article content and sets menu item
    ↓
error.php includes template index.php
    ↓
Template renders custom 404 page with full layout
    ↓
User sees beautiful custom 404 page!
```

### Content Protection

The plugin automatically protects configured 404 pages from:
- **Deletion**: Prevents deletion of configured articles and menu items
- **Unpublishing**: Prevents changing state to unpublished
- **State Changes**: Reverts any state changes and shows error message

This ensures your 404 pages remain functional at all times.

## Configuration Examples

### Single Language Site

```
404 Error Pages:
- Language: * (All)
- Menu Item: "Error 404 Page"
```

### Multilingual Site

```
404 Error Pages:
- Language: en-GB
  Menu Item: "404 Page (English)"

- Language: nl-NL
  Menu Item: "404 Pagina (Nederlands)"

- Language: de-DE
  Menu Item: "404 Seite (Deutsch)"
```

### Fallback Configuration

```
404 Error Pages:
- Language: nl-NL
  Menu Item: "404 Pagina (Nederlands)"

- Language: * (All)
  Menu Item: "404 Page (Default)"
```

The plugin will first try to find a language-specific page, then fall back to the wildcard (`*`) configuration if no match is found.

## Troubleshooting

### 404 Page Not Showing

1. ✅ **Check plugin is enabled**: System → Plugins
2. ✅ **Check configuration**: Plugin has 404 pages configured
3. ✅ **Check template modification**: GLOBALS check is in place
4. ✅ **Check article is published**: The configured 404 article is published
5. ✅ **Check menu item is published**: The configured menu item is published
6. ✅ **Clear cache**: System → Clear Cache

### Page Shows Without Styling

1. ✅ **Check template modification location**: Ensure you modified the correct file
2. ✅ **Check for template overrides**: Some templates have multiple layout files
3. ✅ **Check browser console**: Look for CSS/JS loading errors

### Protection Not Working

1. ✅ **Check plugin is enabled**: The plugin must be enabled for protection to work
2. ✅ **Check configuration**: The item must be configured in the plugin settings
3. ✅ **Clear cache**: Sometimes configuration changes need cache clearing

## Uninstallation

Before uninstalling:

1. **Remove template modification**: Restore the original `<jdoc:include type="component"/>` code
2. **Disable plugin**: System → Plugins → Disable the plugin
3. **Uninstall**: System → Extensions → Manage → Uninstall

## Development

### Building from Source

```bash
git clone git@github.com:hans2103/plg_system_error404.git
cd plg_system_error404
zip -r plg_system_error404.zip * -x "*.git*" "*.DS_Store" "README.md"
```

### Version Numbering

This plugin follows semantic versioning:
- **Major**: Breaking changes requiring manual intervention
- **Minor**: New features, backward compatible
- **Patch**: Bug fixes, backward compatible

## Support

- **Issues**: [GitHub Issues](https://github.com/hans2103/plg_system_error404/issues)
- **Website**: [https://hkweb.nl](https://hkweb.nl)
- **Email**: info@hkweb.nl

## Credits

- **Author**: HKweb
- **Inspired by**: [Perfect Web Team](https://www.perfectwebteam.com/) - This plugin was inspired by the Joomla 4 content plugin [PWT404](https://github.com/perfectwebteam/plg_content_pwt404) and has been modified to be a Joomla 6 system plugin
- **License**: GNU General Public License v3.0 or later
- **Joomla**: Compatible with Joomla 6.x

## License

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Changelog

### Version 26.06.00 (2026-02-09 - Week 6)

**Initial Release**

- ✨ Custom multilingual 404 error pages
- ✨ Content protection (prevents deletion/unpublishing)
- ✨ Template integration with full layout support
- ✨ Language-specific configuration with fallback
- ✨ Easy plugin configuration interface
- 📝 Comprehensive documentation
- 🔄 Automatic update server support
