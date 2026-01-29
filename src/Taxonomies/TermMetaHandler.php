<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Taxonomies;

/**
 * Handles reading and writing Yoast SEO meta fields for taxonomy terms.
 * Note: Yoast stores taxonomy meta in the 'wpseo_taxonomy_meta' option, not in term_meta.
 */
final class TermMetaHandler {

	/**
	 * Yoast meta keys for terms (stored in wpseo_taxonomy_meta option).
	 * Note: These are different from post meta keys.
	 */
	public const META_TITLE       = 'wpseo_title';
	public const META_DESCRIPTION = 'wpseo_desc';
	public const META_FOCUSKW     = 'wpseo_focuskw';
	public const META_CANONICAL   = 'wpseo_canonical';
	public const META_NOINDEX     = 'wpseo_noindex';
	public const META_NOFOLLOW    = 'wpseo_nofollow';

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
		$tax_meta = get_option( 'wpseo_taxonomy_meta', [] );
		$term     = get_term( $term_id );
		
		if ( ! $term || is_wp_error( $term ) ) {
			return [
				'title'       => '',
				'description' => '',
				'focuskw'     => '',
				'canonical'   => '',
				'noindex'     => '',
				'nofollow'    => '',
			];
		}
		
		$meta = $tax_meta[ $term->taxonomy ][ $term_id ] ?? [];
		
		return [
			'title'       => (string) ( $meta[ self::META_TITLE ] ?? '' ),
			'description' => (string) ( $meta[ self::META_DESCRIPTION ] ?? '' ),
			'focuskw'     => (string) ( $meta[ self::META_FOCUSKW ] ?? '' ),
			'canonical'   => (string) ( $meta[ self::META_CANONICAL ] ?? '' ),
			'noindex'     => (string) ( $meta[ self::META_NOINDEX ] ?? '' ),
			'nofollow'    => (string) ( $meta[ self::META_NOFOLLOW ] ?? '' ),
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

		// Get the current taxonomy meta option.
		$tax_meta = get_option( 'wpseo_taxonomy_meta', [] );
		
		// Initialize the taxonomy and term arrays if they don't exist.
		if ( ! isset( $tax_meta[ $taxonomy ] ) ) {
			$tax_meta[ $taxonomy ] = [];
		}
		if ( ! isset( $tax_meta[ $taxonomy ][ $term_id ] ) ) {
			$tax_meta[ $taxonomy ][ $term_id ] = [];
		}
		
		$updated = false;

		if ( isset( $data['title'] ) && '' !== $data['title'] ) {
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_TITLE ] = sanitize_text_field( $data['title'] );
			$updated = true;
		}

		if ( isset( $data['description'] ) && '' !== $data['description'] ) {
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_DESCRIPTION ] = sanitize_textarea_field( $data['description'] );
			$updated = true;
		}

		if ( isset( $data['focuskw'] ) && '' !== $data['focuskw'] ) {
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_FOCUSKW ] = sanitize_text_field( $data['focuskw'] );
			$updated = true;
		}

		if ( isset( $data['canonical'] ) && '' !== $data['canonical'] ) {
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_CANONICAL ] = esc_url_raw( $data['canonical'] );
			$updated = true;
		}

		// Handle noindex.
		if ( isset( $data['noindex'] ) && '' !== $data['noindex'] ) {
			$noindex_value = $this->parse_boolean_value( $data['noindex'] );
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_NOINDEX ] = $noindex_value ? 'noindex' : '';
			$updated = true;
		}

		// Handle nofollow.
		if ( isset( $data['nofollow'] ) && '' !== $data['nofollow'] ) {
			$nofollow_value = $this->parse_boolean_value( $data['nofollow'] );
			$tax_meta[ $taxonomy ][ $term_id ][ self::META_NOFOLLOW ] = $nofollow_value ? 'nofollow' : '';
			$updated = true;
		}

		if ( $updated ) {
			// Prevent complete array validation.
			$tax_meta['wpseo_already_validated'] = true;
			update_option( 'wpseo_taxonomy_meta', $tax_meta );

			// Rebuild the Yoast indexable for this term so changes appear on frontend.
			$this->rebuild_term_indexable( $term_id, $taxonomy );
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
	 * @param string $value The meta value (typically 'noindex'/'nofollow' or empty).
	 * @return string 'yes' or 'no'.
	 */
	public function format_boolean_for_export( string $value ): string {
		return ( 'noindex' === $value || 'nofollow' === $value || '1' === $value ) ? 'yes' : 'no';
	}

	/**
	 * Rebuild the Yoast indexable for a term.
	 *
	 * Yoast SEO caches term metadata in the yoast_indexable table.
	 * After updating the wpseo_taxonomy_meta option directly, we need to
	 * rebuild the indexable so changes appear on the frontend.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	private function rebuild_term_indexable( int $term_id, string $taxonomy ): void {
		// Check if Yoast SEO's indexable system is available.
		if ( ! class_exists( 'Yoast\WP\SEO\Main' ) ) {
			return;
		}

		try {
			$container = \YoastSEO()->classes;

			if ( ! $container ) {
				return;
			}

			// Get the indexable repository and term builder from Yoast's DI container.
			$indexable_repository = $container->get( 'Yoast\WP\SEO\Repositories\Indexable_Repository' );
			$term_builder         = $container->get( 'Yoast\WP\SEO\Builders\Indexable_Term_Builder' );

			if ( ! $indexable_repository || ! $term_builder ) {
				return;
			}

			// Find existing indexable or create a new one.
			$indexable = $indexable_repository->find_by_id_and_type( $term_id, 'term' );

			if ( ! $indexable ) {
				$indexable = $indexable_repository->create_for_id_and_type( $term_id, 'term' );
			}

			// Rebuild the indexable with fresh data.
			$term_builder->build( $term_id, $indexable );
			$indexable->save();
		} catch ( \Exception $e ) {
			// Silently fail - the meta is still saved, just not immediately visible.
			// The indexable will be rebuilt on next page visit or manual edit.
		}
	}
}
