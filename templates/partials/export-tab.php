<?php
/**
 * Export tab template.
 *
 * @package WagadaDigital\YoastMetadata
 *
 * @var array $post_types Array of supported post types.
 */

defined( 'ABSPATH' ) || exit;

use WagadaDigital\YoastMetadata\Core\Plugin;
?>
<div class="yoast-metadata-card">
    <h2><?php esc_html_e( 'Export to CSV', 'yoast-metadata' ); ?></h2>

    <form id="yoast-metadata-export-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr( Plugin::PREFIX . 'export_csv' ); ?>">
        <?php wp_nonce_field( Plugin::PREFIX . 'nonce', 'nonce' ); ?>

        <div class="yoast-metadata-export-options">
            <div class="yoast-metadata-export-option">
                <label><?php esc_html_e( 'Post Types', 'yoast-metadata' ); ?></label>
                <div class="yoast-metadata-checkbox-list">
                    <?php foreach ( $post_types as $slug => $post_type ) : ?>
                        <label>
                            <input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $slug ); ?>" checked>
                            <?php echo esc_html( $post_type->labels->name ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="yoast-metadata-export-option">
                <label><?php esc_html_e( 'Filter', 'yoast-metadata' ); ?></label>
                <div class="yoast-metadata-checkbox-list">
                    <label>
                        <input type="checkbox" name="empty_meta" id="yoast-metadata-empty-meta" value="1">
                        <?php esc_html_e( 'Only posts without SEO metadata', 'yoast-metadata' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="has_meta" value="1">
                        <?php esc_html_e( 'Only posts with SEO metadata', 'yoast-metadata' ); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="yoast-metadata-export-count" id="yoast-metadata-export-count">
            <?php esc_html_e( 'Calculating...', 'yoast-metadata' ); ?>
        </div>

        <div class="yoast-metadata-actions">
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php esc_html_e( 'Download CSV', 'yoast-metadata' ); ?>
            </button>
        </div>
    </form>
</div>

<div class="yoast-metadata-card">
    <h2><?php esc_html_e( 'Export Format', 'yoast-metadata' ); ?></h2>
    <p><?php esc_html_e( 'The exported CSV will contain the following columns:', 'yoast-metadata' ); ?></p>
    <table class="widefat striped" style="max-width: 800px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Column', 'yoast-metadata' ); ?></th>
                <th><?php esc_html_e( 'Description', 'yoast-metadata' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>url</code></td>
                <td><?php esc_html_e( 'The full URL (permalink) of the post', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>post_type</code></td>
                <td><?php esc_html_e( 'The post type (post, page, product, etc.)', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>title</code></td>
                <td><?php esc_html_e( 'Yoast SEO title', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>description</code></td>
                <td><?php esc_html_e( 'Yoast SEO meta description', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>focuskw</code></td>
                <td><?php esc_html_e( 'Focus keyphrase', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>canonical</code></td>
                <td><?php esc_html_e( 'Canonical URL (if set)', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>noindex</code></td>
                <td><?php esc_html_e( 'Allow search engines to show this content (yes = don\'t show, no = show)', 'yoast-metadata' ); ?></td>
            </tr>
            <tr>
                <td><code>nofollow</code></td>
                <td><?php esc_html_e( 'Should search engines follow links (yes = don\'t follow, no = follow)', 'yoast-metadata' ); ?></td>
            </tr>
        </tbody>
    </table>
</div>
