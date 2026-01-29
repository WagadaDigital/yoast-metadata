<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\Taxonomies;

use WagadaDigital\YoastMetadata\Core\Plugin;
use WP_Taxonomy;

/**
 * Discovers and manages supported taxonomies.
 */
final class TaxonomyRegistry {

	/**
	 * Cached supported taxonomies.
	 *
	 * @var array<string, WP_Taxonomy>|null
	 */
	private ?array $taxonomies = null;

	/**
	 * Get all supported taxonomies.
	 *
	 * @return array<string, WP_Taxonomy>
	 */
	public function get_supported_taxonomies(): array {
		if ( null !== $this->taxonomies ) {
			return $this->taxonomies;
		}

		$taxonomies = get_taxonomies(
			[
				'public' => true,
			],
			'objects'
		);

		// Remove post formats by default.
		unset( $taxonomies['post_format'] );

		/**
		 * Filter supported taxonomies.
		 *
		 * @param array<string, WP_Taxonomy> $taxonomies Array of taxonomy objects.
		 */
		$this->taxonomies = apply_filters( Plugin::PREFIX . 'supported_taxonomies', $taxonomies );

		return $this->taxonomies;
	}

	/**
	 * Get taxonomy labels for dropdown.
	 *
	 * @return array<string, string>
	 */
	public function get_taxonomy_labels(): array {
		$labels = [];
		foreach ( $this->get_supported_taxonomies() as $slug => $taxonomy ) {
			$labels[ $slug ] = $taxonomy->labels->name;
		}
		return $labels;
	}

	/**
	 * Check if a taxonomy is supported.
	 */
	public function is_supported( string $taxonomy ): bool {
		return isset( $this->get_supported_taxonomies()[ $taxonomy ] );
	}

	/**
	 * Get taxonomy object by slug.
	 */
	public function get_taxonomy( string $slug ): ?WP_Taxonomy {
		return $this->get_supported_taxonomies()[ $slug ] ?? null;
	}

	/**
	 * Clear cached taxonomies.
	 */
	public function clear_cache(): void {
		$this->taxonomies = null;
	}
}
