<?php
/**
 * Import tab template.
 *
 * @package WagadaDigital\YoastMetadata
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="yoast-metadata-card">
    <h2><?php esc_html_e( 'Import CSV', 'yoast-metadata' ); ?></h2>

    <div class="yoast-metadata-format-info">
        <strong><?php esc_html_e( 'CSV Format:', 'yoast-metadata' ); ?></strong>
        <?php esc_html_e( 'Your CSV must include a header row with at least a', 'yoast-metadata' ); ?>
        <code>url</code>
        <?php esc_html_e( 'column. Optional columns:', 'yoast-metadata' ); ?>
        <code>title</code>, <code>description</code>, <code>focuskw</code>, <code>canonical</code>, <code>noindex</code>, <code>nofollow</code>
        <br><br>
        <strong><?php esc_html_e( 'Robot settings:', 'yoast-metadata' ); ?></strong>
        <?php esc_html_e( 'Use', 'yoast-metadata' ); ?> <code>yes</code> <?php esc_html_e( 'or', 'yoast-metadata' ); ?> <code>no</code>
        <?php esc_html_e( 'for noindex/nofollow columns.', 'yoast-metadata' ); ?>
        <code>noindex=yes</code> <?php esc_html_e( 'means "Don\'t show in search results".', 'yoast-metadata' ); ?>
        <code>nofollow=yes</code> <?php esc_html_e( 'means "Don\'t follow links".', 'yoast-metadata' ); ?>
    </div>

    <div class="yoast-metadata-import-options" style="margin-bottom: 20px;">
        <label style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="yoast-metadata-skip-existing" value="1">
            <span><?php esc_html_e( 'Skip posts/pages that already have a meta description', 'yoast-metadata' ); ?></span>
        </label>
        <p class="description" style="margin-left: 26px;">
            <?php esc_html_e( 'When enabled, posts with an existing Yoast meta description will be skipped during import.', 'yoast-metadata' ); ?>
        </p>
    </div>

    <div class="yoast-metadata-upload-area">
        <input type="file" id="yoast-metadata-csv-file" accept=".csv">
        <label for="yoast-metadata-csv-file">
            <span class="dashicons dashicons-upload"></span>
            <p>
                <strong><?php esc_html_e( 'Drop your CSV file here or click to upload', 'yoast-metadata' ); ?></strong>
            </p>
            <p class="description"><?php esc_html_e( 'Maximum recommended: 1000 rows per file', 'yoast-metadata' ); ?></p>
        </label>
    </div>

    <div class="yoast-metadata-progress">
        <div class="yoast-metadata-progress-bar">
            <div class="yoast-metadata-progress-fill"></div>
        </div>
        <div class="yoast-metadata-progress-text"></div>
    </div>

    <div class="yoast-metadata-preview">
        <h3>
            <?php esc_html_e( 'Preview', 'yoast-metadata' ); ?>
            (<span id="yoast-metadata-total-rows">0</span> <?php esc_html_e( 'rows', 'yoast-metadata' ); ?>)
        </h3>
        <div style="overflow-x: auto;">
            <table class="yoast-metadata-preview-table">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
        <p class="description"><?php esc_html_e( 'Showing first 50 rows.', 'yoast-metadata' ); ?></p>
    </div>

    <div class="yoast-metadata-actions">
        <button type="button" id="yoast-metadata-start-import" class="button button-primary" style="display: none;">
            <?php esc_html_e( 'Start Import', 'yoast-metadata' ); ?>
        </button>
    </div>

    <div class="yoast-metadata-results">
        <h3><?php esc_html_e( 'Import Results', 'yoast-metadata' ); ?></h3>
        <div class="yoast-metadata-result-items"></div>
    </div>
</div>

<div class="yoast-metadata-card">
    <h2><?php esc_html_e( 'Example CSV', 'yoast-metadata' ); ?></h2>
    <div style="overflow-x: auto;">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>url</th>
                    <th>title</th>
                    <th>description</th>
                    <th>noindex</th>
                    <th>nofollow</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>https://example.com/about</td>
                    <td>About Us - Company Name</td>
                    <td>Learn more about our company and mission.</td>
                    <td>no</td>
                    <td>no</td>
                </tr>
                <tr>
                    <td>https://example.com/private</td>
                    <td>Private Page</td>
                    <td>This page should not appear in search.</td>
                    <td>yes</td>
                    <td>yes</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
