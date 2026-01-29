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
			'number'     => $filters['per_page'] ?? self::TERMS_PER_PAGE,
			'offset'     => ( ( $filters['page'] ?? 1 ) - 1 ) * ( $filters['per_page'] ?? self::TERMS_PER_PAGE ),
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		];

		// Filter by meta existence.
		if ( ! empty( $filters['has_meta'] ) ) {
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => '_yoast_wpseo_title',
					'compare' => 'EXISTS',
				],
				[
					'key'     => '_yoast_wpseo_metadesc',
					'compare' => 'EXISTS',
				],
			];
		}

		// Filter by empty meta (terms needing SEO).
		if ( ! empty( $filters['empty_meta'] ) ) {
			$args['meta_query'] = [
				'relation' => 'AND',
				[
					'relation' => 'OR',
					[
						'key'     => '_yoast_wpseo_title',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_yoast_wpseo_title',
						'value'   => '',
						'compare' => '=',
					],
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_yoast_wpseo_metadesc',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_yoast_wpseo_metadesc',
						'value'   => '',
						'compare' => '=',
					],
				],
			];
		}

		// Search filter.
		if ( ! empty( $filters['search'] ) ) {
			$args['search'] = sanitize_text_field( $filters['search'] );
		}

		return $args;
	}

	/**
	 * Execute the query.
	 *
	 * @param array<string, mixed> $args WP_Term_Query arguments.
	 * @return \WP_Term[]
	 */
	public function query( array $args ): array {
		$query = new WP_Term_Query( $args );

		return $query->get_terms() ?: [];
	}

	/**
	 * Get total count for a query.
	 *
	 * @param array<string, mixed> $filters Export filters.
	 */
	public function get_total_count( array $filters = [] ): int {
		$args           = $this->build_args( $filters );
		$args['number'] = 0; // Get all terms for counting.
		$args['fields'] = 'count';
		unset( $args['offset'] );

		$query = new WP_Term_Query( $args );

		return (int) $query->get_terms();
	}
}
