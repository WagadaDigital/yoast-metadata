<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Import;

use WagadaDigital\YoastMetadata\Core\Plugin;
use WagadaDigital\YoastMetadata\PostTypes\MetaHandler;
use Exception;

/**
 * Handles AJAX import requests.
 */
final class ImportHandler {

    private MetaHandler $meta_handler;
    private ?CsvParser $parser = null;
    private ?BatchProcessor $processor = null;

    public function __construct( MetaHandler $meta_handler ) {
        $this->meta_handler = $meta_handler;
    }

    /**
     * Register AJAX handlers.
     */
    public function register(): void {
        add_action( 'wp_ajax_' . Plugin::PREFIX . 'upload_csv', [ $this, 'handle_upload' ] );
        add_action( 'wp_ajax_' . Plugin::PREFIX . 'start_import', [ $this, 'handle_start_import' ] );
        add_action( 'wp_ajax_' . Plugin::PREFIX . 'process_batch', [ $this, 'handle_process_batch' ] );
    }

    /**
     * Handle CSV file upload and preview.
     */
    public function handle_upload(): void {
        $this->verify_request();

        if ( empty( $_FILES['csv_file'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'yoast-metadata' ) ] );
        }

        $file = $_FILES['csv_file'];

        // Validate file type.
        $allowed_types = [ 'text/csv', 'application/csv', 'text/plain' ];
        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file type. Please upload a CSV file.', 'yoast-metadata' ) ] );
        }

        try {
            $parser = $this->get_parser();
            $rows   = $parser->parse( $file['tmp_name'] );

            if ( empty( $rows ) ) {
                wp_send_json_error( [ 'message' => __( 'CSV file contains no valid data rows.', 'yoast-metadata' ) ] );
            }

            // Store rows for import.
            $processor = $this->get_processor();
            $import_id = $processor->store_import( $rows );

            // Return preview data (first 50 rows).
            $preview_rows = array_slice( $rows, 0, 50 );

            wp_send_json_success([
                'import_id' => $import_id,
                'total'     => count( $rows ),
                'headers'   => $parser->get_headers(),
                'preview'   => $preview_rows,
            ]);
        } catch ( Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Handle starting the import process.
     */
    public function handle_start_import(): void {
        $this->verify_request();

        $import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';

        if ( empty( $import_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid import ID.', 'yoast-metadata' ) ] );
        }

        $processor = $this->get_processor();
        $progress  = $processor->get_progress( $import_id );

        if ( null === $progress ) {
            wp_send_json_error( [ 'message' => __( 'Import session not found or expired.', 'yoast-metadata' ) ] );
        }

        wp_send_json_success([
            'import_id' => $import_id,
            'total'     => $progress['total'],
        ]);
    }

    /**
     * Handle processing a batch.
     */
    public function handle_process_batch(): void {
        $this->verify_request();

        $import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';

        if ( empty( $import_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid import ID.', 'yoast-metadata' ) ] );
        }

        $processor = $this->get_processor();
        $result    = $processor->process_batch( $import_id );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( [ 'message' => $result['error'] ] );
        }

        wp_send_json_success( $result );
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
     * Get CSV parser instance.
     */
    private function get_parser(): CsvParser {
        if ( null === $this->parser ) {
            $this->parser = new CsvParser();
        }
        return $this->parser;
    }

    /**
     * Get batch processor instance.
     */
    private function get_processor(): BatchProcessor {
        if ( null === $this->processor ) {
            $this->processor = new BatchProcessor( $this->meta_handler );
        }
        return $this->processor;
    }
}
