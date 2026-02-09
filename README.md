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

## Quick Start

**Want to get up and running fast?** Follow these condensed steps:

### 1. Install & Enable
- Download from [Releases](https://github.com/hans2103/plg_system_error404/releases)
- Install via **System → Extensions → Install**
- Enable via **System → Plugins**

### 2. Configure
- Open plugin settings → **404 Error Pages**
- Add item: Language (`*` for all), Menu Item (your 404 page)
- Save

### 3. Create error.php
Download [error.php.template](https://raw.githubusercontent.com/hans2103/plg_system_error404/main/error.php.template) → save as `/templates/YOUR_TEMPLATE/error.php`

### 4. Modify Component Include
In `/templates/YOUR_TEMPLATE/index.php` or `/html/layouts/layout/main.php`:

**Change:** `<jdoc:include type="component"/>`

**To:**
```php
<?php if (isset($GLOBALS['error_page_component_output'])) : ?>
    <?php echo $GLOBALS['error_page_component_output']; ?>
<?php else : ?>
    <jdoc:include type="component"/>
<?php endif; ?>
```

### 5. Test
Visit `https://yoursite.com/non-existent-page` → Should see your custom 404 page!

---

**Need detailed instructions?** Continue reading below.

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

### 3. Modify Your Template (REQUIRED - 2 Files)

The plugin requires **two template modifications** to display custom 404 content while keeping the original URL intact.

#### Why Two Files?

To display custom 404 pages while keeping the original URL intact, the plugin needs to:

1. **Intercept the error** (via `error.php`) - Catches 404 errors before Joomla renders the default error page
2. **Render custom content** (via `main.php` or `index.php`) - Displays your custom 404 article within your full template

Without both modifications, you'll either see the default error page or the URL will redirect to `/404-page`.

#### Step 1: Modify Main Layout File

**Location:** Usually one of these:
```
/templates/YOUR_TEMPLATE/index.php
/templates/YOUR_TEMPLATE/html/layouts/layout/main.php
```

**Purpose:** Checks if custom 404 content is available and renders it

**What it does:**
- Checks if the Error404 plugin has prepared custom content
- If yes, renders the custom 404 content
- If no, renders the normal component output (business as usual)

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

#### Step 2: Create error.php Override (REQUIRED)

**Location:** `/templates/YOUR_TEMPLATE/error.php`

**Purpose:** Catches 404 errors and triggers the plugin to prepare custom content

**What it does:**
- Detects when a 404 error occurs
- Boots the Error404 plugin
- Calls the plugin to render the custom 404 article
- Includes your template's normal `index.php` for full layout rendering
- Falls back to system error page for other error codes (403, 500, etc.)

**Result:** URL stays the same (e.g., `/this-does-not-exist`) while showing custom 404 content

**Quick Method:**
1. Download [error.php.template](https://raw.githubusercontent.com/hans2103/plg_system_error404/main/error.php.template) from this repository
2. Copy it to `/templates/YOUR_TEMPLATE/error.php`
3. Update the docblock with your template name and copyright year

**Manual Method:**

Create the file at:
```
/templates/YOUR_TEMPLATE/error.php
```

Add this content:
```php
<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.YOUR_TEMPLATE
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @var Joomla\CMS\Document\ErrorDocument $this */

if (!isset($this->error)) {
    $this->error = new Exception('Error');
    $this->debug = false;
}

// Set proper HTTP status code
http_response_code($this->error->getCode());

// For 404 errors, try to load custom error page via plugin
if ($this->error->getCode() === 404) {
    $app = Factory::getApplication();

    // Check if error404 plugin set a menu item ID
    if (isset($GLOBALS['error404_menu_item_id']) && $GLOBALS['error404_menu_item_id'] > 0) {
        try {
            // Boot the plugin and call its render method
            $plugin = $app->bootPlugin('error404', 'system');

            if (method_exists($plugin, 'render404PageFromErrorDocument')) {
                $success = $plugin->render404PageFromErrorDocument(
                    (int) $GLOBALS['error404_menu_item_id'],
                    $this
                );

                // If successful, include the normal template
                if ($success && isset($GLOBALS['error_page_component_output'])) {
                    $templatePath = JPATH_THEMES . '/' . $app->getTemplate() . '/index.php';

                    if (file_exists($templatePath)) {
                        include $templatePath;
                        return;
                    }
                }
            }
        } catch (Exception $e) {
            // Fall through to default error page
        }
    }
}

// For all other errors, use Joomla's default system error template
$systemErrorTemplate = JPATH_THEMES . '/system/error.php';

if (file_exists($systemErrorTemplate)) {
    include $systemErrorTemplate;
} else {
    // Fallback basic error display
    echo '<!DOCTYPE html><html><head><title>Error ' . $this->error->getCode() . '</title></head><body>';
    echo '<h1>Error ' . $this->error->getCode() . '</h1>';
    echo '<p>' . htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
}
```

**Important Notes:**
- Replace `YOUR_TEMPLATE` with your actual template name in the docblock
- This file is generic and will work with any template
- For 404 errors, it uses the plugin to render custom content
- For other errors (403, 500, etc.), it falls back to Joomla's system error template
- **Without this file, the URL will redirect** to the 404 page instead of staying on the original URL

#### Why Both Files Are Required

1. **error.php**: Catches the 404 error and boots the plugin to prepare content
2. **main.php/index.php**: Renders the prepared content within your template layout

This approach ensures:
- ✅ URL stays the same (`/non-existent` shows 404 content)
- ✅ Full template rendering (header, footer, navigation)
- ✅ Proper HTTP 404 status code
- ✅ Works with any Joomla template

### 4. Create Your Custom 404 Articles

1. Create articles for your 404 error pages (one per language if multilingual)
2. Add engaging content, helpful links, search functionality, etc.
3. Create menu items pointing to these articles (can be hidden menu items)
4. Configure these menu items in the plugin settings

### 5. Test Your Installation

**Visit a non-existent page:** `https://yoursite.com/this-does-not-exist`

**Verify these items:**
- ✅ You see your custom 404 article content
- ✅ The page includes your site's header, footer, and navigation
- ✅ The URL shows `/this-does-not-exist` (not `/404-page`)
- ✅ HTTP response is 404 (check in browser DevTools → Network tab)

**If something's not working:**
- Check that `error.php` exists in your template directory
- Check that `main.php` or `index.php` has the `$GLOBALS` check
- Verify the plugin is enabled (System → Plugins)
- Verify your 404 article and menu item are published
- Clear Joomla cache (System → Clear Cache)

## How It Works

### Architecture

```
Non-existent URL (/this-page-does-not-exist)
    ↓
Joomla throws RouteNotFoundException (404)
    ↓
Plugin's onError event fires
    ↓
Plugin stores menu item ID in $GLOBALS['error404_menu_item_id']
    ↓
Joomla renders template's error.php
    ↓
error.php checks for $GLOBALS['error404_menu_item_id']
    ↓
error.php boots plugin and calls render404PageFromErrorDocument()
    ↓
Plugin prepares article content → $GLOBALS['error_page_component_output']
    ↓
error.php includes template's index.php
    ↓
Template renders normally (header, footer, navigation)
    ↓
main.php checks $GLOBALS['error_page_component_output']
    ↓
Custom 404 content rendered within full template
    ↓
User sees beautiful custom 404 page (URL unchanged!)
```

### Key Features

- **URL Preservation**: The browser URL stays `/this-page-does-not-exist` instead of redirecting
- **Full Template**: Custom 404 page includes your complete template (header, navigation, footer)
- **SEO Friendly**: Proper HTTP 404 status code maintained
- **Generic Implementation**: error.php works with any template, no template-specific logic

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

## Frequently Asked Questions

### Can I skip the error.php file?

**No**, not if you want the URL to stay the same. Without `error.php`:
- The plugin could redirect to `/404-page` (URL changes)
- Or the default Joomla error page would show

The `error.php` file is essential for intercepting the error before Joomla renders its default error page.

### Is error.php template-specific?

**No!** The provided `error.php` is generic and works with any template. The only template-specific part is including `index.php`, which all templates have. You can use the exact same `error.php` across different templates.

### What if my template already has an error.php?

Replace it with the provided template. The new `error.php` handles both:
- **404 errors** → Handled by the Error404 plugin (custom page)
- **Other errors** → Falls back to Joomla's system error template (403, 500, etc.)

Your existing custom error handling for non-404 errors will be preserved through the system default.

### Can I customize the error.php?

Yes, but keep these essential parts for the plugin to work:
1. Check for `$GLOBALS['error404_menu_item_id']`
2. Boot the plugin: `$app->bootPlugin('error404', 'system')`
3. Call `render404PageFromErrorDocument()`
4. Include your template's `index.php`

You can add custom logic for other error codes below the 404 handling section.

### Will this affect other error codes (403, 500, etc.)?

**No.** Only 404 errors are handled by the plugin. Other error codes automatically fall back to Joomla's system error template, so your site's handling of forbidden pages, server errors, etc. remains unchanged.

### Do I need to modify error.php for multilingual sites?

**No.** The plugin automatically detects the current language and loads the appropriate 404 page based on your configuration. One `error.php` file works for all languages.

## Troubleshooting

### 404 Page Not Showing

1. ✅ **Check plugin is enabled**: System → Plugins
2. ✅ **Check configuration**: Plugin has 404 pages configured
3. ✅ **Check error.php exists**: `/templates/YOUR_TEMPLATE/error.php` must exist
4. ✅ **Check main.php modification**: `$GLOBALS['error_page_component_output']` check is in place
5. ✅ **Check article is published**: The configured 404 article is published
6. ✅ **Check menu item is published**: The configured menu item is published
7. ✅ **Clear cache**: System → Clear Cache

### URL Redirects Instead of Staying Same

- ✅ **Check error.php exists**: Without `error.php`, the plugin will redirect to the 404 page URL
- ✅ **Check error.php content**: Must include the code from Step 2 above

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

1. **Remove main.php modification**: Restore the original `<jdoc:include type="component"/>` code
2. **Remove error.php**: Delete `/templates/YOUR_TEMPLATE/error.php` (Joomla will use system default)
3. **Disable plugin**: System → Plugins → Disable the plugin
4. **Uninstall**: System → Extensions → Manage → Uninstall

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
