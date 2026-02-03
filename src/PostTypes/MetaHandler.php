<?php

declare(strict_types=1);

namespace WagadaDigital\YoastMetadata\PostTypes;

/**
 * Handles reading and writing Yoast SEO meta fields.
 */
final class MetaHandler {

    /**
     * Yoast meta keys.
     */
    public const META_TITLE       = '_yoast_wpseo_title';
    public const META_DESCRIPTION = '_yoast_wpseo_metadesc';
    public const META_FOCUSKW     = '_yoast_wpseo_focuskw';
    public const META_CANONICAL   = '_yoast_wpseo_canonical';
    public const META_NOINDEX     = '_yoast_wpseo_meta-robots-noindex';
    public const META_NOFOLLOW    = '_yoast_wpseo_meta-robots-nofollow';

    private PostTypeRegistry $registry;

    public function __construct( PostTypeRegistry $registry ) {
        $this->registry = $registry;
    }

    /**
     * Get all Yoast meta for a post.
     *
     * @return array<string, string>
     */
    public function get_meta( int $post_id ): array {
        return [
            'title'       => (string) get_post_meta( $post_id, self::META_TITLE, true ),
            'description' => (string) get_post_meta( $post_id, self::META_DESCRIPTION, true ),
            'focuskw'     => (string) get_post_meta( $post_id, self::META_FOCUSKW, true ),
            'canonical'   => (string) get_post_meta( $post_id, self::META_CANONICAL, true ),
            'noindex'     => (string) get_post_meta( $post_id, self::META_NOINDEX, true ),
            'nofollow'    => (string) get_post_meta( $post_id, self::META_NOFOLLOW, true ),
        ];
    }

    /**
     * Update Yoast meta for a post.
     *
     * @param int                  $post_id Post ID.
     * @param array<string, mixed> $data    Meta data to update.
     * @return bool True if at least one field was updated.
     */
    public function update_meta( int $post_id, array $data ): bool {
        $post = get_post( $post_id );
        if ( ! $post || ! $this->registry->is_supported( $post->post_type ) ) {
            return false;
        }

        $updated = false;

        if ( isset( $data['title'] ) && '' !== $data['title'] ) {
            update_post_meta( $post_id, self::META_TITLE, sanitize_text_field( $data['title'] ) );
            $updated = true;
        }

        if ( isset( $data['description'] ) && '' !== $data['description'] ) {
            update_post_meta( $post_id, self::META_DESCRIPTION, sanitize_textarea_field( $data['description'] ) );
            $updated = true;
        }

        if ( isset( $data['focuskw'] ) && '' !== $data['focuskw'] ) {
            update_post_meta( $post_id, self::META_FOCUSKW, sanitize_text_field( $data['focuskw'] ) );
            $updated = true;
        }

        if ( isset( $data['canonical'] ) && '' !== $data['canonical'] ) {
            update_post_meta( $post_id, self::META_CANONICAL, esc_url_raw( $data['canonical'] ) );
            $updated = true;
        }

        // Handle noindex (Allow search engines to show this content).
        // Values: empty/0/no/false = Yes (index), 1/yes/true = No (noindex).
        if ( isset( $data['noindex'] ) && '' !== $data['noindex'] ) {
            $noindex_value = $this->parse_boolean_value( $data['noindex'] );
            update_post_meta( $post_id, self::META_NOINDEX, $noindex_value ? '1' : '0' );
            $updated = true;
        }

        // Handle nofollow (Should search engines follow links).
        // Values: empty/0/no/false = Yes (follow), 1/yes/true = No (nofollow).
        if ( isset( $data['nofollow'] ) && '' !== $data['nofollow'] ) {
            $nofollow_value = $this->parse_boolean_value( $data['nofollow'] );
            update_post_meta( $post_id, self::META_NOFOLLOW, $nofollow_value ? '1' : '0' );
            $updated = true;
        }

        return $updated;
    }

    /**
     * Resolve a URL to a post ID.
     *
     * Uses VIP function if available. Falls back to flexible slug-based lookup
     * for custom post types with complex URL structures.
     */
    public function url_to_post_id( string $url ): int {
        $url = esc_url_raw( $url );

        // Try standard WordPress function first.
        if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
            $post_id = (int) wpcom_vip_url_to_postid( $url );
        } else {
            $post_id = (int) url_to_postid( $url );
        }

        if ( $post_id > 0 ) {
            return $post_id;
        }

        // Fallback: Try multiple strategies to find the post.
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( ! $path ) {
            return 0;
        }

        $path            = trim( $path, '/' );
        $supported_types = array_keys( $this->registry->get_supported_post_types() );

        // Strategy 1: Try full path (for hierarchical post types like pages).
        $post = get_page_by_path( $path, OBJECT, $supported_types );
        if ( $post ) {
            return $post->ID;
        }

        // Strategy 2: Try each segment as a potential slug (from last to first).
        $segments = explode( '/', $path );
        for ( $i = count( $segments ) - 1; $i >= 0; $i-- ) {
            $slug = $segments[ $i ];
            if ( empty( $slug ) ) {
                continue;
            }

            $post = get_page_by_path( $slug, OBJECT, $supported_types );
            if ( $post ) {
                return $post->ID;
            }
        }

        // Strategy 3: Direct database lookup by post_name for edge cases.
        global $wpdb;
        $placeholders = implode( ',', array_fill( 0, count( $supported_types ), '%s' ) );
        $last_slug    = end( $segments );

        if ( ! empty( $last_slug ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ($placeholders) AND post_status = 'publish' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    array_merge( [ $last_slug ], $supported_types )
                )
            );

            if ( $post_id ) {
                return (int) $post_id;
            }
        }

        return 0;
    }

    /**
     * Get the permalink for a post.
     */
    public function get_post_url( int $post_id ): string {
        return (string) get_permalink( $post_id );
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
