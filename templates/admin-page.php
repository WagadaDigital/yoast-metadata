<?php
/**
 * Admin page template.
 *
 * @package WagadaDigital\YoastMetadata
 *
 * @var array  $post_types Array of supported post types.
 * @var array  $taxonomies Array of supported taxonomies.
 * @var string $active_tab Current active tab.
 */

defined( 'ABSPATH' ) || exit;

use WagadaDigital\YoastMetadata\Admin\AdminPage;
use WagadaDigital\YoastMetadata\Core\Plugin;
?>
<div class="wrap yoast-metadata-wrap">
    <h1><?php esc_html_e( 'Yoast Metadata', 'yoast-metadata' ); ?></h1>

    <nav class="yoast-metadata-tabs">
        <a href="<?php echo esc_url( AdminPage::get_page_url( 'import' ) ); ?>"
           class="<?php echo 'import' === $active_tab ? 'active' : ''; ?>">
            <?php esc_html_e( 'Import', 'yoast-metadata' ); ?>
        </a>
        <a href="<?php echo esc_url( AdminPage::get_page_url( 'export' ) ); ?>"
           class="<?php echo 'export' === $active_tab ? 'active' : ''; ?>">
            <?php esc_html_e( 'Export', 'yoast-metadata' ); ?>
        </a>
        <span class="yoast-metadata-tab-separator"></span>
        <a href="<?php echo esc_url( AdminPage::get_page_url( 'taxonomy-import' ) ); ?>"
           class="<?php echo 'taxonomy-import' === $active_tab ? 'active' : ''; ?>">
            <?php esc_html_e( 'Taxonomy Import', 'yoast-metadata' ); ?>
        </a>
        <a href="<?php echo esc_url( AdminPage::get_page_url( 'taxonomy-export' ) ); ?>"
           class="<?php echo 'taxonomy-export' === $active_tab ? 'active' : ''; ?>">
            <?php esc_html_e( 'Taxonomy Export', 'yoast-metadata' ); ?>
        </a>
    </nav>

    <?php if ( 'import' === $active_tab ) : ?>
        <?php include __DIR__ . '/partials/import-tab.php'; ?>
    <?php elseif ( 'export' === $active_tab ) : ?>
        <?php include __DIR__ . '/partials/export-tab.php'; ?>
    <?php elseif ( 'taxonomy-import' === $active_tab ) : ?>
        <?php include __DIR__ . '/partials/taxonomy-import-tab.php'; ?>
    <?php elseif ( 'taxonomy-export' === $active_tab ) : ?>
        <?php include __DIR__ . '/partials/taxonomy-export-tab.php'; ?>
    <?php else : ?>
        <?php include __DIR__ . '/partials/import-tab.php'; ?>
    <?php endif; ?>
</div>
