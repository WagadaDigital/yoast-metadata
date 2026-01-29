<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Taxonomies;

use WagadaDigital\YoastMetadata\Core\Plugin;

/**
 * Handles CSV export functionality for taxonomy terms.
 */
final class TaxonomyExportHandler {

	private TermMetaHandler $meta_handler;
	private TaxonomyRegistry $taxonomy_registry;
	private ?TermQueryBuilder $query_builder = null;

	public function __construct( TermMetaHandler $meta_handler, TaxonomyRegistry $taxonomy_registry ) {
		$this->meta_handler      = $meta_handler;
		$this->taxonomy_registry = $taxonomy_registry;
	}

	/**
	 * Register AJAX handlers.
	 */
	public function register(): void {
		add_action( 'wp_ajax_' . Plugin::PREFIX . 'export_taxonomy_csv', [ $this, 'handle_export' ] );
		add_action( 'wp_ajax_' . Plugin::PREFIX . 'get_taxonomy_export_count', [ $this, 'handle_get_count' ] );
	}

	/**
	 * Handle CSV export request.
	 */
	public function handle_export(): void {
		$this->verify_request();

		$filters = $this->get_filters_from_request();

		// Set headers for CSV download.
		$filename = 'yoast-taxonomy-metadata-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write CSV header.
		fputcsv( $output, [ 'slug', 'taxonomy', 'name', 'url', 'title', 'description', 'focuskw', 'canonical', 'noindex', 'nofollow' ] );

		// Process in batches to handle large exports.
		$page          = 1;
		$query_builder = $this->get_query_builder();
		$per_page      = 100;

		do {
			$filters['page']     = $page;
			$filters['per_page'] = $per_page;
			$args                = $query_builder->build_args( $filters );
			$terms               = $query_builder->query( $args );

			foreach ( $terms as $term ) {
				$meta = $this->meta_handler->get_meta( $term->term_id );
				$url  = $this->meta_handler->get_term_url( $term->term_id, $term->taxonomy );

				fputcsv( $output, [
					$term->slug,
					$term->taxonomy,
					$term->name,
					$url,
					$meta['title'],
					$meta['description'],
					$meta['focuskw'],
					$meta['canonical'],
					$this->meta_handler->format_boolean_for_export( $meta['noindex'] ),
					$this->meta_handler->format_boolean_for_export( $meta['nofollow'] ),
				] );
			}

			$page++;
		} while ( count( $terms ) === $per_page );

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

		// Taxonomies filter.
		if ( ! empty( $_REQUEST['taxonomies'] ) ) {
			$taxonomies = is_array( $_REQUEST['taxonomies'] )
				? array_map( 'sanitize_key', $_REQUEST['taxonomies'] )
				: [ sanitize_key( $_REQUEST['taxonomies'] ) ];

			// Validate taxonomies.
			$valid_taxonomies = [];
			foreach ( $taxonomies as $taxonomy ) {
				if ( $this->taxonomy_registry->is_supported( $taxonomy ) ) {
					$valid_taxonomies[] = $taxonomy;
				}
			}
			$filters['taxonomies'] = $valid_taxonomies;
		} else {
			$filters['taxonomies'] = array_keys( $this->taxonomy_registry->get_supported_taxonomies() );
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
	private function get_query_builder(): TermQueryBuilder {
		if ( null === $this->query_builder ) {
			$this->query_builder = new TermQueryBuilder();
		}
		return $this->query_builder;
	}
}
