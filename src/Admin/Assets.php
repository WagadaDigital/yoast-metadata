<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Admin;

use Holo\YoastMetadata\Core\Plugin;

/**
 * Handles enqueueing of admin scripts and styles.
 */
final class Assets {

    private string $plugin_url;
    private string $plugin_path;

    public function __construct( string $plugin_url, string $plugin_path ) {
        $this->plugin_url  = $plugin_url;
        $this->plugin_path = $plugin_path;
    }

    /**
     * Register asset hooks.
     */
    public function register(): void {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( 'tools_page_' . Plugin::SLUG !== $hook ) {
            return;
        }

        wp_enqueue_style(
            Plugin::PREFIX . 'admin',
            $this->plugin_url . 'assets/css/admin.css',
            [],
            Plugin::VERSION
        );

        wp_enqueue_script(
            Plugin::PREFIX . 'admin',
            $this->plugin_url . 'assets/js/admin.js',
            [ 'jquery' ],
            Plugin::VERSION,
            true
        );

        wp_localize_script(
            Plugin::PREFIX . 'admin',
            'yoastMetadata',
            [
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( Plugin::PREFIX . 'nonce' ),
                'i18n'     => [
                    'processing'   => __( 'Processing...', 'yoast-metadata' ),
                    'complete'     => __( 'Complete!', 'yoast-metadata' ),
                    'error'        => __( 'An error occurred.', 'yoast-metadata' ),
                    'confirmStart' => __( 'Start import?', 'yoast-metadata' ),
                    'updated'      => __( 'Updated:', 'yoast-metadata' ),
                    'skipped'      => __( 'Skipped:', 'yoast-metadata' ),
                    'failed'       => __( 'Failed:', 'yoast-metadata' ),
                ],
            ]
        );
    }
}
