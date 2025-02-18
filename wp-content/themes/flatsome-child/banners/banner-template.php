<?php
/**
 * Template Name: Banner Template
 *
 * Template for displaying responsive banners using Flatsome's native grid system
 *
 * @package RandomBanners
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate banner HTML output
 *
 * @param array $banner Banner data array
 * @return string HTML output
 */
function banner_template_html($banner) {
    if (!is_array($banner)) {
        return '';
    }

    // Determine link target based on URL
    $site_domain = parse_url(get_site_url(), PHP_URL_HOST);
    $banner_domain = parse_url($banner['url'], PHP_URL_HOST);
    $is_external = $site_domain !== $banner_domain;

    ob_start();

    $output = sprintf(
        '<div class="random-banner random-banner-%s" data-banner-id="%s">',
        sanitize_title($banner['location']),
        esc_attr($banner['ID'])
    );

    // Handle HTML snippet banners
    if (!empty($banner['html_snippet'])) {
        $output .= $banner['html_snippet'];
    } else {
        // Standard banner layout
        $output .= generate_standard_banner($banner, $is_external);
    }

    $output .= '</div>';

    echo $output;
    return ob_get_clean();
}

/**
 * Generate standard banner layout
 *
 * @param array $banner Banner data
 * @param bool $is_external Whether link is external
 * @return string Banner HTML
 */
function generate_standard_banner($banner, $is_external) {
    $output = sprintf(
        '<a href="%s" %s class="banner-link" data-banner-id="%s">',
        esc_url($banner['url']),
        $is_external ? 'target="_blank" rel="noopener"' : '',
        esc_attr($banner['ID'])
    );

    // Main banner container using Flatsome's native responsive classes
    $output .= '<div class="row align-middle stack-row">';

    // Text content section
    $output .= '<div class="col large-9 medium-9 small-12">';
    $output .= '<div class="col-inner">';
    
    // Banner title
    $output .= sprintf(
        '<h3>%s</h3>',
        esc_html($banner['title'])
    );

    // Banner description
    $output .= sprintf(
        '<div class="banner-description">%s</div>',
        wp_kses_post($banner['description'])
    );

    $output .= '</div>'; // End col-inner
    $output .= '</div>'; // End text content col

    // Image section
    $output .= '<div class="col large-3 medium-3 small-12">';
    $output .= '<div class="col-inner">';

    if ($banner['image']) {
        $output .= sprintf(
            '<img src="%s" alt="%s">',
            esc_url($banner['image']['url']),
            esc_attr($banner['title'])
        );
    }

    $output .= '</div>'; // End col-inner
    $output .= '</div>'; // End image col
    
    $output .= '</div>'; // End row
    $output .= '</a>'; // End banner link

    return $output;
}