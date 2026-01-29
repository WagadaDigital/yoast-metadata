# Yoast Metadata

Bulk manage Yoast SEO metadata across posts, pages, and custom post types via CSV import/export.

## Description

Yoast Metadata is a WordPress plugin that allows you to bulk update Yoast SEO meta titles and descriptions by uploading a CSV file. It also supports exporting your existing SEO metadata for backup or editing purposes.

**Features:**

- Import SEO metadata from CSV files
- Export existing metadata to CSV
- Support for posts, pages, WooCommerce products, and custom post types
- Batch processing for large datasets (handles thousands of posts)
- Progress tracking with real-time updates
- Drag & drop file upload
- Preview data before importing
- Filter exports by post type or metadata status

## Requirements

- WordPress 6.1 or higher
- PHP 7.4 or higher
- [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) plugin (must be installed and activated)

## Installation

1. Upload the `yoast-metadata` folder to `/wp-content/plugins/`
2. Run `composer install` in the plugin directory
3. Activate the plugin through the WordPress admin
4. Go to **Tools > Yoast Metadata**

## CSV Format

### Import

Your CSV must include a header row with at least a `url` column. Optional columns:

| Column | Description |
|--------|-------------|
| `url` | **Required.** The full URL of the post/page |
| `title` | Yoast SEO title |
| `description` | Yoast SEO meta description |
| `focuskw` | Focus keyphrase |
| `canonical` | Canonical URL |

**Example:**

```csv
url,title,description
https://example.com/about,About Us - Company Name,Learn more about our company and mission.
https://example.com/services,Our Services | Company Name,Explore our wide range of professional services.
```

### Export

Exported CSV files contain the following columns:

- `url` - Post permalink
- `post_type` - Post type (post, page, product, etc.)
- `title` - Yoast SEO title
- `description` - Yoast SEO meta description
- `focuskw` - Focus keyphrase
- `canonical` - Canonical URL (if set)

## Usage

### Importing

1. Go to **Tools > Yoast Metadata**
2. Click the Import tab (default)
3. Drag and drop your CSV file or click to select
4. Review the preview (shows first 50 rows)
5. Click **Start Import**
6. Wait for processing to complete

### Exporting

1. Go to **Tools > Yoast Metadata**
2. Click the Export tab
3. Select which post types to include
4. Optionally filter by metadata status
5. Click **Download CSV**

## Hooks

### Filter: `yoast_metadata_supported_post_types`

Modify which post types are supported:

```php
add_filter( 'yoast_metadata_supported_post_types', function( $post_types ) {
    // Remove a post type
    unset( $post_types['product'] );

    // Or add a custom one
    $post_types['my_custom_type'] = get_post_type_object( 'my_custom_type' );

    return $post_types;
});
```

## Development

```bash
# Install dependencies
composer install

# Generate autoload files
composer dump-autoload
```

## Changelog

### 1.0.0
- Initial release
- CSV import with batch processing
- CSV export with post type filtering
- Support for all public post types
- WordPress VIP compatible

## License

GPL-2.0-or-later
