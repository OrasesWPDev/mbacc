<?php
/**
 * Banner Manager Class
 *
 * This class handles the core functionality for the banner system including:
 * - Random banner selection
 * - Shortcode registration and processing
 * - Cache control for banner display
 * - Banner validation (active status and date ranges)
 * - Statistics tracking integration
 * - Admin columns and filtering for banner types
 *
 * @package BannerManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly for security
}

class Banner_Manager
{
    /**
     * Singleton instance
     *
     * @var Banner_Manager
     */
    private static $instance = null;

    /**
     * Banner types from ACF configuration
     *
     * @var array
     */
    private $banner_types = array(
        'Home Page - Standard Ad' => 'Home Page - Standard Ad',
        'Home Page - Platinum Sponsor' => 'Home Page - Platinum Sponsor',
        'Interior Page' => 'Interior Page'
    );

    /**
     * Get singleton instance
     *
     * @return Banner_Manager
     * @since 1.0.0
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        error_log('Banner_Manager instance created');
        $this->init();
    }

    /**
     * Initialize the banner system
     *
     * @since 1.0.0
     */
    private function init() {
        error_log('Initializing Banner Manager');
        // Register the [random_banner] shortcode
        add_shortcode('random_banner', array($this, 'random_banner_shortcode'));

        // Add cache control headers
        add_action('template_redirect', function() {
            error_log('template_redirect action triggered');
            $this->disable_banner_caching();
        });

        // Register admin columns and filters
        $this->register_admin_filters();

        // Include the template files
        require_once dirname(__FILE__) . '/banner-template.php';
        require_once dirname(__FILE__) . '/banner-statistics.php';
    }

    /**
     * Register admin columns and filters
     */
    private function register_admin_filters() {
        add_filter('manage_banner_posts_columns', array($this, 'add_banner_type_column'));
        add_action('manage_banner_posts_custom_column', array($this, 'display_banner_type_column'), 10, 2);
        add_filter('manage_edit-banner_sortable_columns', array($this, 'make_banner_type_column_sortable'));
        add_action('restrict_manage_posts', array($this, 'add_banner_type_filter_dropdown'));
        add_action('pre_get_posts', array($this, 'filter_banners_by_type'));
    }

    /**
     * Add Banner Type column to banners admin list
     */
    public function add_banner_type_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new_columns[$key] = $value;
                $new_columns['banner_type'] = 'Banner Type';
            } else {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }

    /**
     * Display Banner Type column content
     */
    public function display_banner_type_column($column, $post_id) {
        if ($column === 'banner_type') {
            $banner_type = get_field('location', $post_id);
            echo esc_html($banner_type);
        }
    }

    /**
     * Make Banner Type column sortable
     */
    public function make_banner_type_column_sortable($columns) {
        $columns['banner_type'] = 'location';
        return $columns;
    }

    /**
     * Add Banner Type filter dropdown
     */
    public function add_banner_type_filter_dropdown($post_type) {
        if ($post_type !== 'banner') {
            return;
        }

        $selected = isset($_GET['banner_type_filter']) ? sanitize_text_field($_GET['banner_type_filter']) : '';
        
        echo '<select name="banner_type_filter">';
        echo '<option value="">All Banner Types</option>';
        
        foreach ($this->banner_types as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($selected, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    /**
     * Filter banners by type
     */
    public function filter_banners_by_type($query) {
        global $pagenow;

        if (!(is_admin()
            && $pagenow === 'edit.php'
            && isset($_GET['post_type'])
            && $_GET['post_type'] === 'banner'
            && isset($_GET['banner_type_filter'])
            && !empty($_GET['banner_type_filter']))) {
            return;
        }

        $query->query_vars['meta_key'] = 'location';
        $query->query_vars['meta_value'] = sanitize_text_field($_GET['banner_type_filter']);
    }

    /**
     * Get a random banner
     *
     * @param string $location Optional location to filter banners
     * @return array|false Banner data array or false if no banner found
     */
    public function get_random_banner($location = '')
    {
        $args = array(
            'post_type' => 'banner',
            'posts_per_page' => -1,
            'orderby' => 'rand',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'active',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        if (!empty($location)) {
            $args['meta_query'][] = array(
                'key' => 'location',
                'value' => $location,
                'compare' => '='
            );
        }

        $banner_query = new WP_Query($args);
        $valid_banners = array();

        if ($banner_query->have_posts()) {
            while ($banner_query->have_posts()) {
                $banner_query->the_post();

                $start_date = get_field('start_datetime');
                $stop_date = get_field('stop_datetime');
                $current_time = current_time('timestamp');

                if ((!empty($start_date) && strtotime($start_date) > $current_time) ||
                    (!empty($stop_date) && strtotime($stop_date) < $current_time)) {
                    continue;
                }

                $valid_banners[] = array(
                    'ID' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_field('url'),
                    'html_snippet' => get_field('html_snippet'),
                    'image' => get_field('image'),
                    'location' => get_field('location'),
                    'description' => get_the_content()
                );
            }
        }

        wp_reset_postdata();

        error_log('Valid banners found: ' . count($valid_banners));

        if (!empty($valid_banners)) {
            $selected_banner = $valid_banners[array_rand($valid_banners)];

            do_action('banner_impression', $selected_banner['ID']);

            if (!empty($selected_banner['html_snippet'])) {
                add_action('wp_head', function () use ($selected_banner) {
                    echo $selected_banner['html_snippet'];
                });
            }

            return $selected_banner;
        }

        return false;
    }

    /**
     * Shortcode handler for [random_banner]
     * Handles banner display and prevents duplicate processing of same location
     */
    public function random_banner_shortcode($atts) {
    static $processed_locations = array();

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'location' => ''
    ), $atts);

    // Create a unique key for this shortcode instance
    $location_key = $atts['location'];

    // If we've already processed this location, return empty to prevent duplicates
    if (isset($processed_locations[$location_key])) {
        error_log('Preventing duplicate processing of banner location: ' . $location_key);
        return '';
    }

    // Mark this location as processed
    $processed_locations[$location_key] = true;

    global $random_banner_detected;
    $random_banner_detected = true;

    $banners = $this->get_all_banners($atts['location']);

    // Debug: Log banner processing
    error_log('Processing banner location: ' . $location_key . ' - Found banners: ' . count($banners));

    // Prepare JavaScript for console logging
    $banner_ids = array_map(function($banner) {
        return $banner['ID'];
    }, $banners);

    ob_start();

    if (!empty($banners)) {
        echo '<div class="banner-rotation-container">';
        foreach ($banners as $index => $banner) {
            $class_name = 'banner-slide';
            if ($index === 0 || count($banners) === 1) {
                $class_name .= ' active';
            }

            echo '<div class="' . esc_attr($class_name) . '">';
            echo banner_template_html($banner);
            echo '</div>';
        }
        echo '</div>';

        // Only add rotation script if there's more than one banner
        if (count($banners) > 1) {
            wp_enqueue_script(
                'banner-rotation',
                get_stylesheet_directory_uri() . '/banners/banner-rotation.js',
                array('jquery'),
                '1.0.0',
                true
            );
        }

        // Output JavaScript to log banner IDs to the console
        echo '<script>console.log("Banners found: ", ' . json_encode($banner_ids) . ');</script>';
    } else {
        // Debug: Log when no banners are found
        error_log('No banners found for location: ' . $atts['location']);
    }

    $output = ob_get_clean();

    // Debug: Log final output generation
    error_log('Banner output generated for location: ' . $location_key);

    return $output;
}
    /**
     * Get all banners for a specific location
     */
    public function get_all_banners($location = '') {
        $args = array(
            'post_type' => 'banner',
            'posts_per_page' => -1,
            'orderby' => 'rand',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'active',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        if (!empty($location)) {
            $args['meta_query'][] = array(
                'key' => 'location',
                'value' => $location,
                'compare' => '='
            );
        }

        $banner_query = new WP_Query($args);
        $valid_banners = array();

        if ($banner_query->have_posts()) {
            while ($banner_query->have_posts()) {
                $banner_query->the_post();

                $start_date = get_field('start_datetime');
                $stop_date = get_field('stop_datetime');
                $current_time = current_time('timestamp');

                if ((!empty($start_date) && strtotime($start_date) > $current_time) ||
                    (!empty($stop_date) && strtotime($stop_date) < $current_time)) {
                    continue;
                }

                $valid_banners[] = array(
                    'ID' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_field('url'),
                    'html_snippet' => get_field('html_snippet'),
                    'image' => get_field('image'),
                    'location' => get_field('location'),
                    'description' => get_the_content()
                );
            }
        }

        wp_reset_postdata();

        error_log('Valid banners found: ' . count($valid_banners));

        return $valid_banners;
    }

    /**
     * Disable caching for pages with banners
     */
    public function disable_banner_caching() {
        add_action('wp_footer', function() {
            global $random_banner_detected;
            error_log('Global flag state in wp_footer: ' . (isset($random_banner_detected) ? 'set' : 'not set'));
            if (!empty($random_banner_detected)) {
                error_log('Disabling cache for page with random_banner shortcode');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
        });
    }
}

// Initialize the Banner Manager
Banner_Manager::get_instance();