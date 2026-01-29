<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Taxonomies;

use WP_Term_Query;

/**
 * Builds term queries for exporting taxonomy terms.
 */
final class TermQueryBuilder {

	/**
	 * Default terms per page for exports.
	 */
	private const TERMS_PER_PAGE = 100;

	/**
	 * Build query arguments for export.
	 *
	 * @param array<string, mixed> $filters Export filters.
	 * @return array<string, mixed> WP_Term_Query arguments.
	 */
	public function build_args( array $filters = [] ): array {
		$args = [
			'taxonomy'   => $filters['taxonomies'] ?? [ 'category', 'post_tag' ],
			'hide_empty' => false,
			'number'     => 0, // Get all terms since we filter in PHP.
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		];

		// Search filter.
		if ( ! empty( $filters['search'] ) ) {
			$args['search'] = sanitize_text_field( $filters['search'] );
		}

		return $args;
	}

	/**
	 * Execute the query and apply filters.
	 *
	 * @param array<string, mixed> $args WP_Term_Query arguments.
	 * @return \WP_Term[]
	 */
	public function query( array $args ): array {
		$query = new WP_Term_Query( $args );
		$terms = $query->get_terms() ?: [];

		// Get filters from args if stored there, or return all terms.
		if ( ! isset( $args['_filters'] ) ) {
			return $terms;
		}

		$filters = $args['_filters'];

		// Get Yoast taxonomy meta.
		$tax_meta = get_option( 'wpseo_taxonomy_meta', [] );

		// Apply meta filters.
		if ( ! empty( $filters['has_meta'] ) || ! empty( $filters['empty_meta'] ) ) {
			$terms = array_filter( $terms, function ( $term ) use ( $tax_meta, $filters ) {
				$meta = $tax_meta[ $term->taxonomy ][ $term->term_id ] ?? [];

				$has_title = ! empty( $meta['wpseo_title'] );
				$has_desc  = ! empty( $meta['wpseo_desc'] );

				// Filter by meta existence.
				if ( ! empty( $filters['has_meta'] ) ) {
					return $has_title || $has_desc;
				}

				// Filter by empty meta.
				if ( ! empty( $filters['empty_meta'] ) ) {
					return ! $has_title && ! $has_desc;
				}

				return true;
			} );
		}

		// Apply pagination after filtering.
		$per_page = $filters['per_page'] ?? self::TERMS_PER_PAGE;
		$page     = $filters['page'] ?? 1;
		$offset   = ( $page - 1 ) * $per_page;

		return array_slice( $terms, $offset, $per_page );
	}

	/**
	 * Get total count for a query.
	 *
	 * @param array<string, mixed> $filters Export filters.
	 */
	public function get_total_count( array $filters = [] ): int {
		$args              = $this->build_args( $filters );
		$args['_filters']  = $filters; // Pass filters for PHP-based filtering.
		$args['fields']    = 'all'; // Need full objects to filter.

		$terms = $this->query( $args );

		return count( $terms );
	}
}
