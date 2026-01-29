<?php
/**
 * Plugin Name:       Yoast Metadata
 * Plugin URI:        https://github.com/WagadaDigital/yoast-metadata
 * Description:       Bulk manage Yoast SEO metadata across posts, pages, and custom post types via CSV import/export.
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Requires Plugins:  wordpress-seo
 * Version:           0.0.6
 * Author:            Marius Paduraru
 * Author URI:        https://github.com/holodev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yoast-metadata
 *
 * @package WagadaDigital\YoastMetadata
 */

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata;

use WagadaDigital\YoastMetadata\Core\Plugin;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'YOAST_METADATA_VERSION', '1.0.0' );
define( 'YOAST_METADATA_FILE', __FILE__ );
define( 'YOAST_METADATA_PATH', plugin_dir_path( __FILE__ ) );
define( 'YOAST_METADATA_URL', plugin_dir_url( __FILE__ ) );

// Autoloader - use custom PSR-4 autoloader instead of Composer.
require_once __DIR__ . '/includes/autoload.php';

/**
 * Initialize the plugin.
 *
 * @return Plugin
 */
function yoast_metadata(): Plugin {
    return Plugin::instance();
}

// Boot the plugin.
yoast_metadata()->init( __FILE__ );