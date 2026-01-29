<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Export;

use Holo\YoastMetadata\Core\Plugin;
use Holo\YoastMetadata\PostTypes\MetaHandler;
use Holo\YoastMetadata\PostTypes\PostTypeRegistry;

/**
 * Handles CSV export functionality.
 */
final class ExportHandler {

    private MetaHandler $meta_handler;
    private PostTypeRegistry $post_type_registry;
    private ?QueryBuilder $query_builder = null;

    public function __construct( MetaHandler $meta_handler, PostTypeRegistry $post_type_registry ) {
        $this->meta_handler       = $meta_handler;
        $this->post_type_registry = $post_type_registry;
    }

    /**
     * Register AJAX handlers.
     */
    public function register(): void {
        add_action( 'wp_ajax_' . Plugin::PREFIX . 'export_csv', [ $this, 'handle_export' ] );
        add_action( 'wp_ajax_' . Plugin::PREFIX . 'get_export_count', [ $this, 'handle_get_count' ] );
    }

    /**
     * Handle CSV export request.
     */
    public function handle_export(): void {
        $this->verify_request();

        $filters = $this->get_filters_from_request();

        // Set headers for CSV download.
        $filename = 'yoast-metadata-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // Write CSV header.
        fputcsv( $output, [ 'url', 'post_type', 'title', 'description', 'focuskw', 'canonical' ] );

        // Process in batches to handle large exports.
        $page = 1;
        $query_builder = $this->get_query_builder();

        do {
            $filters['page'] = $page;
            $args  = $query_builder->build_args( $filters );
            $query = $query_builder->query( $args );

            foreach ( $query->posts as $post ) {
                $meta = $this->meta_handler->get_meta( $post->ID );
                $url  = $this->meta_handler->get_post_url( $post->ID );

                fputcsv( $output, [
                    $url,
                    $post->post_type,
                    $meta['title'],
                    $meta['description'],
                    $meta['focuskw'],
                    $meta['canonical'],
                ]);
            }

            $page++;
        } while ( $query->max_num_pages >= $page );

        fclose( $output );
        exit;
    }

    /**
     * Handle get export count request.
     */
    public function handle_get_count(): void {
        $this->verify_request();

        $filters       = $this->get_filters_from_request();
        $query_builder = $this->get_query_builder();
        $count         = $query_builder->get_total_count( $filters );

        wp_send_json_success( [ 'count' => $count ] );
    }

    /**
     * Get filters from request.
     *
     * @return array<string, mixed>
     */
    private function get_filters_from_request(): array {
        $filters = [];

        // Post types filter.
        if ( ! empty( $_REQUEST['post_types'] ) ) {
            $post_types = is_array( $_REQUEST['post_types'] )
                ? array_map( 'sanitize_key', $_REQUEST['post_types'] )
                : [ sanitize_key( $_REQUEST['post_types'] ) ];

            // Validate post types.
            $valid_types = [];
            foreach ( $post_types as $type ) {
                if ( $this->post_type_registry->is_supported( $type ) ) {
                    $valid_types[] = $type;
                }
            }
            $filters['post_types'] = $valid_types;
        } else {
            $filters['post_types'] = array_keys( $this->post_type_registry->get_supported_post_types() );
        }

        // Empty meta filter.
        if ( ! empty( $_REQUEST['empty_meta'] ) ) {
            $filters['empty_meta'] = true;
        }

        // Has meta filter.
        if ( ! empty( $_REQUEST['has_meta'] ) ) {
            $filters['has_meta'] = true;
        }

        return $filters;
    }

    /**
     * Verify AJAX request.
     */
    private function verify_request(): void {
        check_ajax_referer( Plugin::PREFIX . 'nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'yoast-metadata' ) ], 403 );
        }
    }

    /**
     * Get query builder instance.
     */
    private function get_query_builder(): QueryBuilder {
        if ( null === $this->query_builder ) {
            $this->query_builder = new QueryBuilder();
        }
        return $this->query_builder;
    }
}
