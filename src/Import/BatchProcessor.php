<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Import;

use Holo\YoastMetadata\Core\Plugin;
use Holo\YoastMetadata\PostTypes\MetaHandler;

/**
 * Handles batch processing of CSV imports.
 */
final class BatchProcessor {

    private const TRANSIENT_PREFIX = 'yoast_metadata_import_';

    private MetaHandler $meta_handler;

    public function __construct( MetaHandler $meta_handler ) {
        $this->meta_handler = $meta_handler;
    }

    /**
     * Store import data in a transient for batch processing.
     *
     * @param array<int, array<string, string>> $rows     CSV rows.
     * @param int                               $batch_size Rows per batch.
     * @return string Import ID for tracking.
     */
    public function store_import( array $rows, int $batch_size = 50 ): string {
        $import_id = wp_generate_uuid4();

        $data = [
            'rows'        => $rows,
            'batch_size'  => $batch_size,
            'total'       => count( $rows ),
            'processed'   => 0,
            'updated'     => [],
            'skipped'     => [],
            'failed'      => [],
            'started_at'  => time(),
        ];

        set_transient( self::TRANSIENT_PREFIX . $import_id, $data, HOUR_IN_SECONDS );

        return $import_id;
    }

    /**
     * Process the next batch of rows.
     *
     * @param string $import_id Import ID.
     * @return array{complete: bool, processed: int, total: int, batch_results: array<string, array<string>>}
     */
    public function process_batch( string $import_id ): array {
        $data = get_transient( self::TRANSIENT_PREFIX . $import_id );

        if ( false === $data ) {
            return [
                'complete'      => true,
                'processed'     => 0,
                'total'         => 0,
                'batch_results' => [
                    'updated' => [],
                    'skipped' => [],
                    'failed'  => [],
                ],
                'error'         => __( 'Import session expired.', 'yoast-metadata' ),
            ];
        }

        $batch_start   = $data['processed'];
        $batch_end     = min( $batch_start + $data['batch_size'], $data['total'] );
        $batch_rows    = array_slice( $data['rows'], $batch_start, $data['batch_size'] );
        $batch_results = $this->process_rows( $batch_rows );

        // Update tracking data.
        $data['processed'] = $batch_end;
        $data['updated']   = array_merge( $data['updated'], $batch_results['updated'] );
        $data['skipped']   = array_merge( $data['skipped'], $batch_results['skipped'] );
        $data['failed']    = array_merge( $data['failed'], $batch_results['failed'] );

        $complete = $data['processed'] >= $data['total'];

        if ( $complete ) {
            delete_transient( self::TRANSIENT_PREFIX . $import_id );
        } else {
            set_transient( self::TRANSIENT_PREFIX . $import_id, $data, HOUR_IN_SECONDS );
        }

        return [
            'complete'      => $complete,
            'processed'     => $data['processed'],
            'total'         => $data['total'],
            'batch_results' => $batch_results,
            'totals'        => [
                'updated' => count( $data['updated'] ),
                'skipped' => count( $data['skipped'] ),
                'failed'  => count( $data['failed'] ),
            ],
        ];
    }

    /**
     * Process a batch of rows.
     *
     * @param array<int, array<string, string>> $rows Rows to process.
     * @return array{updated: array<string>, skipped: array<string>, failed: array<string>}
     */
    private function process_rows( array $rows ): array {
        $results = [
            'updated' => [],
            'skipped' => [],
            'failed'  => [],
        ];

        foreach ( $rows as $row ) {
            $url = $row['url'] ?? '';

            if ( empty( $url ) ) {
                $results['skipped'][] = __( 'Empty URL', 'yoast-metadata' );
                continue;
            }

            $post_id = $this->meta_handler->url_to_post_id( $url );

            if ( 0 === $post_id ) {
                $results['failed'][] = $url;
                continue;
            }

            // Check if there's anything to update.
            $has_data = ! empty( $row['title'] ) || ! empty( $row['description'] ) ||
                        ! empty( $row['focuskw'] ) || ! empty( $row['canonical'] );

            if ( ! $has_data ) {
                $results['skipped'][] = $url;
                continue;
            }

            $updated = $this->meta_handler->update_meta( $post_id, [
                'title'       => $row['title'] ?? '',
                'description' => $row['description'] ?? '',
                'focuskw'     => $row['focuskw'] ?? '',
                'canonical'   => $row['canonical'] ?? '',
            ]);

            if ( $updated ) {
                $results['updated'][] = $url;
            } else {
                $results['skipped'][] = $url;
            }
        }

        return $results;
    }

    /**
     * Get import progress.
     *
     * @param string $import_id Import ID.
     * @return array<string, mixed>|null Progress data or null if not found.
     */
    public function get_progress( string $import_id ): ?array {
        $data = get_transient( self::TRANSIENT_PREFIX . $import_id );
        if ( false === $data ) {
            return null;
        }

        return [
            'processed' => $data['processed'],
            'total'     => $data['total'],
            'updated'   => count( $data['updated'] ),
            'skipped'   => count( $data['skipped'] ),
            'failed'    => count( $data['failed'] ),
        ];
    }

    /**
     * Cancel an import.
     *
     * @param string $import_id Import ID.
     */
    public function cancel_import( string $import_id ): void {
        delete_transient( self::TRANSIENT_PREFIX . $import_id );
    }
}
