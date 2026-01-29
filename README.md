# Yoast Metadata

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Yoast Metadata is a WordPress plugin for bulk managing Yoast SEO metadata (titles, descriptions) across posts, pages, and custom post types. It supports CSV import/export, batch processing via AJAX, and is designed for scalability on large WordPress installations.

**Key Requirements:**

- Support posts, pages, WooCommerce products, and any registered custom post type
- Use Composer for autoloading and dependency management
- Follow PSR-4 autoloading standards
- Batch processing for large datasets (avoid memory/timeout issues)
- WordPress VIP and multisite compatible

## Build & Development Commands

```bash
# Install dependencies
composer install

# Run PHP CodeSniffer (WordPress Coding Standards)
composer run phpcs

# Auto-fix coding standards issues
composer run phpcbf

# Run PHPUnit tests
composer run test

# Run a single test file
./vendor/bin/phpunit tests/Unit/SomeTest.php

# Run PHPStan static analysis
composer run phpstan

# Generate autoload files after adding new classes
composer dump-autoload
```

## Architecture

### Directory Structure

```
yoast-metadata/
├── src/                          # PSR-4 autoloaded classes (WagadaDigital\YoastMetadata)
│   ├── Admin/                    # Admin-facing functionality
│   │   ├── AdminPage.php         # Main admin page controller
│   │   ├── Assets.php            # Script/style enqueuing
│   │   └── Notices.php           # Admin notices handler
│   ├── Core/                     # Core plugin functionality
│   │   ├── Plugin.php            # Main plugin bootstrap (singleton)
│   │   ├── Container.php         # Simple service container
│   │   └── Hooks.php             # WordPress hooks registration
│   ├── Import/                   # CSV import functionality
│   │   ├── CsvParser.php         # CSV parsing and validation
│   │   ├── BatchProcessor.php    # Chunked processing for large files
│   │   └── ImportHandler.php     # AJAX handler for imports
│   ├── Export/                   # CSV export functionality
│   │   ├── ExportHandler.php     # Export controller
│   │   └── QueryBuilder.php      # Build WP_Query for exports
│   ├── PostTypes/                # Post type support
│   │   ├── PostTypeRegistry.php  # Discover and register supported post types
│   │   └── MetaHandler.php       # Read/write Yoast meta fields
│   └── Contracts/                # Interfaces
│       ├── ImporterInterface.php
│       └── ExporterInterface.php
├── assets/
│   ├── js/                       # JavaScript (vanilla or Alpine.js)
│   └── css/                      # Stylesheets
├── templates/                    # PHP template files for admin views
├── languages/                    # Translation files (.pot, .po, .mo)
├── tests/
│   ├── Unit/                     # PHPUnit unit tests
│   └── Integration/              # WordPress integration tests
├── yoast-metadata.php            # Main plugin file (bootstrap only)
├── uninstall.php                 # Cleanup on plugin deletion
├── composer.json
└── phpcs.xml.dist                # PHPCS configuration
```

### Core Patterns

**Service Container:** Use a simple container in `Core/Container.php` for dependency injection. Avoid complex DI frameworks to keep the plugin lightweight.

**Singleton Bootstrap:** The main `Plugin` class uses singleton pattern, instantiated once in the main plugin file. All other classes should be instantiated through the container.

**Batch Processing:** Large CSV imports must be processed in chunks (50-100 rows per AJAX request) to avoid timeouts. Use WordPress transients or custom DB table to track progress.

**Post Type Discovery:** Use `get_post_types(['public' => true], 'objects')` to dynamically discover supported post types. Allow filtering via `yoast_metadata_supported_post_types` hook.

### Yoast SEO Meta Keys

```php
// Title template
'_yoast_wpseo_title'

// Meta description
'_yoast_wpseo_metadesc'

// Focus keyphrase
'_yoast_wpseo_focuskw'

// Canonical URL
'_yoast_wpseo_canonical'

// Robots meta (noindex, nofollow)
'_yoast_wpseo_meta-robots-noindex'
'_yoast_wpseo_meta-robots-nofollow'
```

### CSV Format

Import/export CSV must include headers:

```
url,post_type,title,description
```

The `post_type` column enables filtering and validation. URL resolution uses `url_to_postid()` (or `wpcom_vip_url_to_postid()` on VIP).

## Coding Standards

- Follow WordPress Coding Standards (WPCS)
- Use strict typing: `declare(strict_types=1);` in all PHP files
- Prefix all hooks, options, and transients with `yoast_metadata_`
- Prefix all database tables with `{$wpdb->prefix}yoast_metadata_`
- All user-facing strings must be translatable via `__()` or `esc_html__()`
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Validate and sanitize all input: `sanitize_text_field()`, `absint()`, `esc_url_raw()`

## AJAX Handlers

Register AJAX actions with both authenticated and unauthenticated hooks when needed:

```php
add_action('wp_ajax_yoast_metadata_import', [$this, 'handleImport']);
// No wp_ajax_nopriv_ - admin only functionality
```

Always verify nonce and capabilities:

```php
check_ajax_referer('yoast_metadata_nonce', 'nonce');
if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Unauthorized'], 403);
}
```

## Testing

Unit tests use PHPUnit with Brain Monkey for WordPress function mocking. Integration tests require a WordPress test environment (wp-env or similar).

```php
// Example unit test structure
namespace WagadaDigital\YoastMetadata\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class CsvParserTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
}
```

## Performance Considerations

- Use `wp_cache_get/set` for repeated queries
- Batch database operations with `$wpdb->query()` for bulk updates when possible
- Implement progress tracking via transients for long-running imports
- Support background processing via WP-Cron for very large datasets (optional feature)
- Limit CSV preview to first 50 rows in admin UI

## WordPress VIP Compatibility

- Use `wpcom_vip_url_to_postid()` when available
- Avoid `file_get_contents()` - use `WP_Filesystem` or `wp_remote_get()`
- No direct database queries without caching
- Follow VIP code analysis rules
