<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Taxonomies;

/**
 * Handles reading and writing Yoast SEO meta fields for taxonomy terms.
 */
final class TermMetaHandler {

	/**
	 * Yoast meta keys for terms.
	 * Note: Yoast uses the same meta keys for terms as for posts.
	 */
	public const META_TITLE       = '_yoast_wpseo_title';
	public const META_DESCRIPTION = '_yoast_wpseo_metadesc';
	public const META_FOCUSKW     = '_yoast_wpseo_focuskw';
	public const META_CANONICAL   = '_yoast_wpseo_canonical';
	public const META_NOINDEX     = '_yoast_wpseo_meta-robots-noindex';
	public const META_NOFOLLOW    = '_yoast_wpseo_meta-robots-nofollow';

	private TaxonomyRegistry $registry;

	public function __construct( TaxonomyRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Get all Yoast meta for a term.
	 *
	 * @return array<string, string>
	 */
	public function get_meta( int $term_id ): array {
		return [
			'title'       => (string) get_term_meta( $term_id, self::META_TITLE, true ),
			'description' => (string) get_term_meta( $term_id, self::META_DESCRIPTION, true ),
			'focuskw'     => (string) get_term_meta( $term_id, self::META_FOCUSKW, true ),
			'canonical'   => (string) get_term_meta( $term_id, self::META_CANONICAL, true ),
			'noindex'     => (string) get_term_meta( $term_id, self::META_NOINDEX, true ),
			'nofollow'    => (string) get_term_meta( $term_id, self::META_NOFOLLOW, true ),
		];
	}

	/**
	 * Update Yoast meta for a term.
	 *
	 * @param int                  $term_id  Term ID.
	 * @param string               $taxonomy Taxonomy name.
	 * @param array<string, mixed> $data     Meta data to update.
	 * @return bool True if at least one field was updated.
	 */
	public function update_meta( int $term_id, string $taxonomy, array $data ): bool {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) || ! $this->registry->is_supported( $taxonomy ) ) {
			return false;
		}

		$updated = false;

		if ( isset( $data['title'] ) && '' !== $data['title'] ) {
			update_term_meta( $term_id, self::META_TITLE, sanitize_text_field( $data['title'] ) );
			$updated = true;
		}

		if ( isset( $data['description'] ) && '' !== $data['description'] ) {
			update_term_meta( $term_id, self::META_DESCRIPTION, sanitize_textarea_field( $data['description'] ) );
			$updated = true;
		}

		if ( isset( $data['focuskw'] ) && '' !== $data['focuskw'] ) {
			update_term_meta( $term_id, self::META_FOCUSKW, sanitize_text_field( $data['focuskw'] ) );
			$updated = true;
		}

		if ( isset( $data['canonical'] ) && '' !== $data['canonical'] ) {
			update_term_meta( $term_id, self::META_CANONICAL, esc_url_raw( $data['canonical'] ) );
			$updated = true;
		}

		// Handle noindex.
		if ( isset( $data['noindex'] ) && '' !== $data['noindex'] ) {
			$noindex_value = $this->parse_boolean_value( $data['noindex'] );
			update_term_meta( $term_id, self::META_NOINDEX, $noindex_value ? '1' : '0' );
			$updated = true;
		}

		// Handle nofollow.
		if ( isset( $data['nofollow'] ) && '' !== $data['nofollow'] ) {
			$nofollow_value = $this->parse_boolean_value( $data['nofollow'] );
			update_term_meta( $term_id, self::META_NOFOLLOW, $nofollow_value ? '1' : '0' );
			$updated = true;
		}

		return $updated;
	}

	/**
	 * Resolve a term by slug and taxonomy.
	 *
	 * @param string $slug     Term slug.
	 * @param string $taxonomy Taxonomy name.
	 * @return int Term ID or 0 if not found.
	 */
	public function get_term_id_by_slug( string $slug, string $taxonomy ): int {
		$term = get_term_by( 'slug', sanitize_title( $slug ), $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return 0;
		}

		return $term->term_id;
	}

	/**
	 * Get the archive URL for a term.
	 */
	public function get_term_url( int $term_id, string $taxonomy ): string {
		$url = get_term_link( $term_id, $taxonomy );

		if ( is_wp_error( $url ) ) {
			return '';
		}

		return (string) $url;
	}

	/**
	 * Parse a boolean value from CSV input.
	 *
	 * Accepts: 1, 0, yes, no, true, false (case-insensitive).
	 *
	 * @param mixed $value The value to parse.
	 * @return bool True if the value represents a truthy value.
	 */
	private function parse_boolean_value( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, [ '1', 'yes', 'true' ], true );
	}

	/**
	 * Format a boolean meta value for export.
	 *
	 * @param string $value The meta value (typically '1' or empty).
	 * @return string 'yes' or 'no'.
	 */
	public function format_boolean_for_export( string $value ): string {
		return ( '1' === $value ) ? 'yes' : 'no';
	}
}
