<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Admin;

use Holo\YoastMetadata\Core\Plugin;
use Holo\YoastMetadata\Import\ImportHandler;
use Holo\YoastMetadata\Export\ExportHandler;
use Holo\YoastMetadata\PostTypes\PostTypeRegistry;

/**
 * Admin page controller.
 */
final class AdminPage {

    private Assets $assets;
    private ImportHandler $import_handler;
    private ExportHandler $export_handler;
    private PostTypeRegistry $post_type_registry;
    private string $plugin_path;

    public function __construct(
        Assets $assets,
        ImportHandler $import_handler,
        ExportHandler $export_handler,
        PostTypeRegistry $post_type_registry,
        string $plugin_path
    ) {
        $this->assets             = $assets;
        $this->import_handler     = $import_handler;
        $this->export_handler     = $export_handler;
        $this->post_type_registry = $post_type_registry;
        $this->plugin_path        = $plugin_path;
    }

    /**
     * Register admin menu and page.
     */
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
    }

    /**
     * Add the plugin menu page.
     */
    public function add_menu_page(): void {
        add_submenu_page(
            'tools.php',
            __( 'Yoast Metadata', 'yoast-metadata' ),
            __( 'Yoast Metadata', 'yoast-metadata' ),
            'manage_options',
            Plugin::SLUG,
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render the admin page.
     */
    public function render_page(): void {
        $post_types = $this->post_type_registry->get_supported_post_types();
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'import';

        include $this->plugin_path . 'templates/admin-page.php';
    }

    /**
     * Get the admin page URL.
     */
    public static function get_page_url( string $tab = '' ): string {
        $url = admin_url( 'tools.php?page=' . Plugin::SLUG );
        if ( $tab ) {
            $url = add_query_arg( 'tab', $tab, $url );
        }
        return $url;
    }
}
