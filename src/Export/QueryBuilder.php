<?php

declare(strict_types=1);

namespace Holo\YoastMetadata\Export;

use WP_Query;

/**
 * Builds WP_Query for exporting posts.
 */
final class QueryBuilder {

    /**
     * Default posts per page for exports.
     */
    private const POSTS_PER_PAGE = 100;

    /**
     * Build query arguments for export.
     *
     * @param array<string, mixed> $filters Export filters.
     * @return array<string, mixed> WP_Query arguments.
     */
    public function build_args( array $filters = [] ): array {
        $args = [
            'post_type'      => $filters['post_types'] ?? [ 'post', 'page' ],
            'post_status'    => 'publish',
            'posts_per_page' => $filters['per_page'] ?? self::POSTS_PER_PAGE,
            'paged'          => $filters['page'] ?? 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
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

        // Filter by empty meta (posts needing SEO).
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
            $args['s'] = sanitize_text_field( $filters['search'] );
        }

        return $args;
    }

    /**
     * Execute the query.
     *
     * @param array<string, mixed> $args WP_Query arguments.
     */
    public function query( array $args ): WP_Query {
        return new WP_Query( $args );
    }

    /**
     * Get total count for a query.
     *
     * @param array<string, mixed> $filters Export filters.
     */
    public function get_total_count( array $filters = [] ): int {
        $args                   = $this->build_args( $filters );
        $args['posts_per_page'] = -1;
        $args['fields']         = 'ids';

        $query = new WP_Query( $args );

        return $query->found_posts;
    }
}
