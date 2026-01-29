<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Core;

use Holo\YoastMetadata\Admin\AdminPage;
use Holo\YoastMetadata\Admin\Assets;
use Holo\YoastMetadata\Import\ImportHandler;
use Holo\YoastMetadata\Export\ExportHandler;
use Holo\YoastMetadata\PostTypes\PostTypeRegistry;
use Holo\YoastMetadata\PostTypes\MetaHandler;

/**
 * Main plugin bootstrap class.
 */
final class Plugin {

    private static ?Plugin $instance = null;
    private Container $container;

    /**
     * Plugin version.
     */
    public const VERSION = '1.0.0';

    /**
     * Plugin slug.
     */
    public const SLUG = 'yoast-metadata';

    /**
     * Option prefix for all plugin options.
     */
    public const PREFIX = 'yoast_metadata_';

    private function __construct() {
        $this->container = new Container();
    }

    /**
     * Get singleton instance.
     */
    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    public function init( string $plugin_file ): void {
        $this->register_services( $plugin_file );
        $this->boot_services();
    }

    /**
     * Register all services in the container.
     */
    private function register_services( string $plugin_file ): void {
        $this->container->set( 'plugin_file', $plugin_file );
        $this->container->set( 'plugin_path', plugin_dir_path( $plugin_file ) );
        $this->container->set( 'plugin_url', plugin_dir_url( $plugin_file ) );

        $this->container->set( PostTypeRegistry::class, function () {
            return new PostTypeRegistry();
        });

        $this->container->set( MetaHandler::class, function ( Container $c ) {
            return new MetaHandler( $c->get( PostTypeRegistry::class ) );
        });

        $this->container->set( ImportHandler::class, function ( Container $c ) {
            return new ImportHandler( $c->get( MetaHandler::class ) );
        });

        $this->container->set( ExportHandler::class, function ( Container $c ) {
            return new ExportHandler( $c->get( MetaHandler::class ), $c->get( PostTypeRegistry::class ) );
        });

        $this->container->set( Assets::class, function ( Container $c ) {
            return new Assets( $c->get( 'plugin_url' ), $c->get( 'plugin_path' ) );
        });

        $this->container->set( AdminPage::class, function ( Container $c ) {
            return new AdminPage(
                $c->get( Assets::class ),
                $c->get( ImportHandler::class ),
                $c->get( ExportHandler::class ),
                $c->get( PostTypeRegistry::class ),
                $c->get( 'plugin_path' )
            );
        });
    }

    /**
     * Boot all services and register hooks.
     */
    private function boot_services(): void {
        add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
    }

    /**
     * Fires after all plugins are loaded.
     */
    public function on_plugins_loaded(): void {
        if ( ! $this->check_dependencies() ) {
            return;
        }

        load_plugin_textdomain( self::SLUG, false, dirname( plugin_basename( $this->container->get( 'plugin_file' ) ) ) . '/languages' );

        if ( is_admin() ) {
            $this->container->get( AdminPage::class )->register();
            $this->container->get( Assets::class )->register();
        }

        $this->container->get( ImportHandler::class )->register();
        $this->container->get( ExportHandler::class )->register();
    }

    /**
     * Check if Yoast SEO is active.
     */
    private function check_dependencies(): bool {
        if ( ! defined( 'WPSEO_VERSION' ) ) {
            add_action( 'admin_notices', [ $this, 'yoast_missing_notice' ] );
            return false;
        }
        return true;
    }

    /**
     * Display admin notice when Yoast SEO is not active.
     */
    public function yoast_missing_notice(): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: %s: Yoast SEO plugin name */
                    esc_html__( '%s requires Yoast SEO to be installed and activated.', 'yoast-metadata' ),
                    '<strong>Yoast Metadata</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get the container.
     */
    public function container(): Container {
        return $this->container;
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}
