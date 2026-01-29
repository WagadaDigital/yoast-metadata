<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\PostTypes;

use Holo\YoastMetadata\Core\Plugin;
use WP_Post_Type;

/**
 * Discovers and manages supported post types.
 */
final class PostTypeRegistry {

    /**
     * Cached supported post types.
     *
     * @var array<string, WP_Post_Type>|null
     */
    private ?array $post_types = null;

    /**
     * Get all supported post types.
     *
     * @return array<string, WP_Post_Type>
     */
    public function get_supported_post_types(): array {
        if ( null !== $this->post_types ) {
            return $this->post_types;
        }

        $post_types = get_post_types(
            [
                'public' => true,
            ],
            'objects'
        );

        // Remove attachments by default.
        unset( $post_types['attachment'] );

        /**
         * Filter supported post types.
         *
         * @param array<string, WP_Post_Type> $post_types Array of post type objects.
         */
        $this->post_types = apply_filters( Plugin::PREFIX . 'supported_post_types', $post_types );

        return $this->post_types;
    }

    /**
     * Get post type labels for dropdown.
     *
     * @return array<string, string>
     */
    public function get_post_type_labels(): array {
        $labels = [];
        foreach ( $this->get_supported_post_types() as $slug => $post_type ) {
            $labels[ $slug ] = $post_type->labels->name;
        }
        return $labels;
    }

    /**
     * Check if a post type is supported.
     */
    public function is_supported( string $post_type ): bool {
        return isset( $this->get_supported_post_types()[ $post_type ] );
    }

    /**
     * Get post type object by slug.
     */
    public function get_post_type( string $slug ): ?WP_Post_Type {
        return $this->get_supported_post_types()[ $slug ] ?? null;
    }

    /**
     * Clear cached post types.
     */
    public function clear_cache(): void {
        $this->post_types = null;
    }
}
