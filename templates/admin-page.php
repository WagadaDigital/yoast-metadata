<?php
/**
 * Admin page template.
 *
 * @package Holo\YoastMetadata
 *
 * @var array  $post_types Array of supported post types.
 * @var string $active_tab Current active tab.
 */

defined( 'ABSPATH' ) || exit;

use Holo\YoastMetadata\Admin\AdminPage;
use Holo\YoastMetadata\Core\Plugin;
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
    </nav>

    <?php if ( 'import' === $active_tab ) : ?>
        <?php include __DIR__ . '/partials/import-tab.php'; ?>
    <?php else : ?>
        <?php include __DIR__ . '/partials/export-tab.php'; ?>
    <?php endif; ?>
</div>
