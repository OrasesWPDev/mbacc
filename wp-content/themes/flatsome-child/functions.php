<?php
/**
 * Flatsome Child Theme Functions
 *
 * @package Flatsome-Child
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define Constants
 * Set up basic constants for file paths
 */
define('CHILD_THEME_DIR', get_stylesheet_directory());
define('CHILD_THEME_URI', get_stylesheet_directory_uri());

/**
 * Required Files
 * Load all required functionality
 */
$required_files = array(
    // Banner functionality
    '/banners/class-banner-manager.php',
    // Team member functionality
    '/team-members/team-member-manager.php',
    // Custom widgets
    '/widgets/class-tag-dropdown-widget.php'
);

foreach ($required_files as $file) {
    $file_path = CHILD_THEME_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

/**
 * Enqueue Styles and Scripts
 * Handle all style and script enqueueing
 */
function child_theme_enqueue_assets() {
    // Banner Styles
    $banner_css_file = CHILD_THEME_DIR . '/banners/banner.css';
    if (file_exists($banner_css_file)) {
        wp_enqueue_style(
            'banner-styles',
            CHILD_THEME_URI . '/banners/banner.css',
            array(),
            filemtime($banner_css_file),
            'all'
        );
    }

    // Banner Scripts
    wp_enqueue_script(
        'banner-rotation',
        CHILD_THEME_URI . '/banners/banner-rotation.js',
        array(),
        filemtime(CHILD_THEME_DIR . '/banners/banner-rotation.js'),
        true
    );

    // Team Members Styles
    $team_css_file = CHILD_THEME_DIR . '/team-members/team-members.css';
    if (file_exists($team_css_file)) {
        wp_enqueue_style(
            'team-members-styles',
            CHILD_THEME_URI . '/team-members/team-members.css',
            array(),
            filemtime($team_css_file)
        );
    }
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_assets');

/**
 * Yoast SEO Breadcrumbs Modification
 * Add Flatsome responsive classes to Yoast SEO breadcrumbs
 *
 * @param string $output The breadcrumb output
 * @return string Modified breadcrumb output
 */
function add_classes_to_yoast_breadcrumbs($output) {
    $responsive_classes = 'text-left large-text-left medium-text-center small-text-center';

    // Add classes to last breadcrumb item
    $output = str_replace(
        '<span class="breadcrumb_last"',
        sprintf('<span class="%s breadcrumb_last"', $responsive_classes),
        $output
    );

    // Wrap entire breadcrumb in responsive classes
    return sprintf('<div class="%s">%s</div>', $responsive_classes, $output);
}
add_filter('wpseo_breadcrumb_output', 'add_classes_to_yoast_breadcrumbs');

/**
 * Theme Setup
 * Additional theme setup functionality
 */
function child_theme_setup() {
    // Add theme support features here if needed
}
add_action('after_setup_theme', 'child_theme_setup');

/**
 * Admin Notices
 * Display admin notices if required files are missing
 */
function child_theme_admin_notices() {
    global $required_files;

    foreach ($required_files as $file) {
        if (!file_exists(CHILD_THEME_DIR . $file)) {
            $message = sprintf(
                __('Warning: Required file %s is missing from the child theme.', 'flatsome'),
                '<code>' . $file . '</code>'
            );
            echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
        }
    }
}
add_action('admin_notices', 'child_theme_admin_notices');