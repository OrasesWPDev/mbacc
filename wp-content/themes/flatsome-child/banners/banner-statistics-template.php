/<?php
/**
 * Custom Admin Template for Banner Statistics
 *
 * Displays banner statistics in a clean, form-like layout
 * Includes:
 * - Banner information
 * - Statistics display
 * - Individual export button
 *
 * @package BannerManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly for security
}

// Get the banner statistics data
$banner_id = get_field('banner_id');
$banner_url = get_field('banner_url');
$impressions = get_field('impressions');
$clicks = get_field('clicks');
$ctr = get_field('ctr');
$price_per_click = get_field('price_per_click');

// Get the banner title
$banner_title = get_the_title($banner_id);
?>

<div class="form-horizontal banner-statistics-display">
    <div class="form-group form-group-static">
        <label class="control-label">Banner Name</label>
        <div class="form-control-static">
            <?php echo esc_html($banner_title); ?>
        </div>
    </div>

    <div class="form-group form-group-static">
        <label class="control-label">URL</label>
        <div class="form-control-static">
            <a href="<?php echo esc_url($banner_url); ?>" rel="external" target="_blank">
                <?php echo esc_url($banner_url); ?>
            </a>
        </div>
    </div>

    <div class="form-group form-group-static">
        <label class="control-label">Impressions</label>
        <div class="form-control-static">
            <?php echo number_format($impressions); ?>
        </div>
    </div>

    <div class="form-group form-group-static">
        <label class="control-label">Clicks</label>
        <div class="form-control-static">
            <?php echo number_format($clicks); ?>
        </div>
    </div>

    <div class="form-group form-group-static">
        <label class="control-label">Price Per Click</label>
        <div class="form-control-static">
            $<?php echo number_format((float)$price_per_click, 2); ?>
        </div>
    </div>

    <div class="form-group form-group-static">
        <label class="control-label">CTR</label>
        <div class="form-control-static">
            <?php echo $ctr; ?>%
        </div>
    </div>

    <div class="export-button">
        <form method="post">
            <?php wp_nonce_field('export_single_banner_stats', 'banner_stats_nonce'); ?>
            <input type="hidden" name="banner_id" value="<?php echo esc_attr($banner_id); ?>">
            <input type="hidden" name="action" value="export_single_banner_stats">
            <button type="submit" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                Export Statistics
            </button>
        </form>
    </div>
</div>