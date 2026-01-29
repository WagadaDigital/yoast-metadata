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

        if ( isset( $data['noindex'] ) ) {
            update_post_meta( $post_id, self::META_NOINDEX, $data['noindex'] ? '1' : '0' );
            $updated = true;
        }

        if ( isset( $data['nofollow'] ) ) {
            update_post_meta( $post_id, self::META_NOFOLLOW, $data['nofollow'] ? '1' : '0' );
            $updated = true;
        }

        return $updated;
    }

    /**
     * Resolve a URL to a post ID.
     *
     * Uses VIP function if available.
     */
    public function url_to_post_id( string $url ): int {
        $url = esc_url_raw( $url );

        if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
            return (int) wpcom_vip_url_to_postid( $url );
        }

        return (int) url_to_postid( $url );
    }

    /**
     * Get the permalink for a post.
     */
    public function get_post_url( int $post_id ): string {
        return (string) get_permalink( $post_id );
    }
}
