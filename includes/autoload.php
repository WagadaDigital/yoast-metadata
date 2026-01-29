<?php
/**
 * PSR-4 Autoloader for Yoast Metadata plugin.
 *
 * @package WagadaDigital\YoastMetadata
 */

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata;

/**
 * PSR-4 autoloader implementation.
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register( function ( $class ) {
	// Plugin namespace prefix.
	$prefix = 'WagadaDigital\\YoastMetadata\\';

	// Base directory for the namespace prefix.
	$base_dir = YOAST_METADATA_PATH . 'src/';

	// Check if the class uses the namespace prefix.
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// No, move to the next registered autoloader.
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class, $len );

	// Replace namespace separators with directory separators in the relative class name,
	// append with .php.
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );
