<?php
/**
 * Banner Statistics Handler
 *
 * Handles tracking, recording, and reporting of banner statistics including:
 * - Impression tracking for paid banners
 * - Click tracking with AJAX support
 * - CTR (Click-Through Rate) calculation
 * - Price Per Click calculation
 * - CSV export functionality (individual and bulk)
 * - Statistical data management
 * - Custom admin interface
 * - Statistics display in banner edit screen
 *
 * @package BannerManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly for security
}

class Banner_Statistics {
    /**
     * Singleton instance
     * Ensures only one instance of Banner_Statistics exists at any time
     *
     * @var Banner_Statistics
     */
    private static $instance = null;

    /**
     * Get singleton instance
     * Creates new instance if none exists
     *
     * @return Banner_Statistics
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * Private to prevent direct creation
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the statistics functionality
     * Sets up all necessary hooks and actions
     */
private function init() {
    // Setup hooks for tracking clicks via AJAX
    add_action('wp_ajax_track_banner_click', array($this, 'track_click'));
    add_action('wp_ajax_nopriv_track_banner_click', array($this, 'track_click'));

    // Add impression tracking
    add_action('banner_impression', array($this, 'track_impression'));

    // Add click tracking script to footer
    add_action('wp_footer', array($this, 'add_click_tracking_script'));

    // Add admin menu for statistics export
    add_action('admin_menu', array($this, 'add_export_menu'));

    // Enqueue admin styles
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

    // Handle single banner export
    add_action('admin_init', array($this, 'handle_single_banner_export'));

    // Customize admin display
    add_action('acf/input/admin_head', array($this, 'customize_admin_display'));
    
    // Add statistics template only for banner_statistic post type
    add_action('edit_form_after_title', function($post) {
        if ($post && $post->post_type === 'banner_statistic') {
            $this->display_statistics_template();
        }
    });
    
    // Keep menu cleanup functionality
    add_action('admin_menu', array($this, 'remove_meta_boxes'));
}

    /**
     * Enqueue admin styles for banner statistics
     */
    public function enqueue_admin_styles() {
        $screen = get_current_screen();
        if ($screen && 'banner_statistic' === $screen->post_type) {
            wp_enqueue_style(
                'banner-statistics-admin',
                get_stylesheet_directory_uri() . '/banners/banner.css',
                array(),
                filemtime(get_stylesheet_directory() . '/banners/banner.css')
            );
        }
    }

    /**
     * Customize the admin display
     * Ensures that the admin interface is tailored for banner statistics
     */
    public function customize_admin_display() {
        global $post;
        if (!$post || 'banner_statistic' !== $post->post_type) {
            return;
        }
    }

    /**
     * Display statistics template
     * Includes the template for displaying statistics in the admin area
     */
    public function display_statistics_template() {
        global $post;
        if (!$post || 'banner_statistic' !== $post->post_type) {
            return;
        }
        include dirname(__FILE__) . '/banner-statistics-template.php';
    }

    /**
     * Remove unnecessary meta boxes and capabilities
     * Cleans up the admin interface for banner statistics
     */
    public function remove_meta_boxes() {
        // Remove default meta boxes
        remove_meta_box('submitdiv', 'banner_statistic', 'side');
        remove_meta_box('slugdiv', 'banner_statistic', 'normal');
        remove_meta_box('postdivrich', 'banner_statistic', 'normal');
        remove_meta_box('postimagediv', 'banner_statistic', 'side');

        // Remove 'Add New' button
        global $submenu;
        if (isset($submenu['edit.php?post_type=banner_statistic'])) {
            foreach ($submenu['edit.php?post_type=banner_statistic'] as $key => $item) {
                if ($item[2] === 'post-new.php?post_type=banner_statistic') {
                    unset($submenu['edit.php?post_type=banner_statistic'][$key]);
                }
            }
        }
    }

    /**
     * Add Statistics Meta Box to Banner
     * Adds a meta box for displaying statistics in the banner edit screen
     */
    public function add_banner_statistics_meta_box() {
        add_meta_box(
            'banner-statistics-meta-box',
            'Banner Statistics',
            array($this, 'render_banner_statistics_meta_box'),
            'banner',
            'normal',
            'high'
        );
    }

    /**
     * Render Statistics Meta Box Content
     * Displays the statistics data in the meta box
     */
public function render_banner_statistics_meta_box($post) {
    // Get statistics for this banner
    $args = array(
        'post_type' => 'banner_statistic',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'banner_id',
                'value' => $post->ID
            )
        )
    );

    $stats = get_posts($args);

    if (!empty($stats)) {
        $stat = $stats[0];
        ?>
        <div class="inside">
            <div class="stats-container">
                <div class="banner-stats-display">
                    <table>
                        <tr>
                            <th>Impressions</th>
                            <td><?php echo number_format(get_field('impressions', $stat->ID)); ?></td>
                        </tr>
                        <tr>
                            <th>Clicks</th>
                            <td><?php echo number_format(get_field('clicks', $stat->ID)); ?></td>
                        </tr>
                        <tr>
                            <th>CTR</th>
                            <td><?php echo get_field('ctr', $stat->ID); ?>%</td>
                        </tr>
                        <?php if (get_field('price', $post->ID)): ?>
                            <tr>
                                <th>Price Per Click</th>
                                <td>$<?php echo number_format((float)get_field('price_per_click', $stat->ID), 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo get_the_modified_date('F j, Y g:i a', $stat->ID); ?></td>
                        </tr>
                    </table>
                </div>
                <!-- Export form should only be submitted when the export button is clicked -->
                <form method="post" action="">
                    <?php wp_nonce_field('export_single_banner_stats', 'banner_stats_nonce'); ?>
                    <input type="hidden" name="banner_id" value="<?php echo esc_attr($post->ID); ?>">
                    <input type="hidden" name="action" value="export_single_banner_stats">
                    <p class="submit">
                        <button type="submit" name="export_stats" class="button button-primary">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                            Export These Statistics
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    } else {
        if (get_field('price', $post->ID)) {
            echo '<p>No statistics available yet for this banner. Statistics will be generated once the banner receives impressions.</p>';
        } else {
            echo '<p>Statistics tracking is only available for banners with a price set.</p>';
        }
    }
}

    /**
     * Track banner impressions
     * Only tracks impressions for banners with a price set
     *
     * @param int $banner_id The ID of the banner being displayed
     */
    public function track_impression($banner_id) {
        // Check if banner has a price set
        $price = get_field('price', $banner_id);
        if (empty($price)) {
            return;
        }

        // Get or create statistics entry
        $stat_id = $this->get_or_create_stat($banner_id);

        // Update impression count
        $impressions = (int)get_field('impressions', $stat_id);
        update_field('impressions', $impressions + 1, $stat_id);

        // Update calculated fields
        $this->update_calculations($stat_id);
    }

    /**
     * Track banner click via AJAX
     * Handles click tracking and updates statistics
     */
    public function track_click() {
        error_log('Track click method called');

        if (!isset($_POST['banner_id']) || !wp_verify_nonce($_POST['nonce'], 'banner_click')) {
            error_log('Invalid request - Banner ID or nonce missing/invalid');
            wp_send_json_error('Invalid request');
        }

        $banner_id = intval($_POST['banner_id']);
        error_log('Processing click for banner ID: ' . $banner_id);

        // Verify this is a paid banner
        $price = get_field('price', $banner_id);
        error_log('Banner price: ' . $price);

        if (empty($price)) {
            error_log('Not a paid banner - Price is empty for banner ID: ' . $banner_id);
            wp_send_json_error('Not a paid banner');
        }

        $stat_id = $this->get_or_create_stat($banner_id);
        error_log('Stat ID for banner: ' . $stat_id);

        $clicks = (int)get_field('clicks', $stat_id);
        $new_clicks = $clicks + 1;
        error_log('Current clicks: ' . $clicks);
        error_log('New clicks value: ' . $new_clicks);

        $update_result = update_field('clicks', $new_clicks, $stat_id);
        error_log('Update result: ' . ($update_result ? 'success' : 'failed'));

        if ($update_result) {
            $this->update_calculations($stat_id);
            error_log('Calculations updated');
            wp_send_json_success(array(
                'message' => 'Click tracked successfully',
                'clicks' => $new_clicks,
                'stat_id' => $stat_id
            ));
        } else {
            error_log('Failed to update clicks');
            wp_send_json_error('Failed to update clicks');
        }
    }

    /**
     * Add click tracking JavaScript
     * Injects JavaScript for tracking banner clicks via AJAX
     */
    public function add_click_tracking_script() {
        ?>
        <script>
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function($) {
                $('.random-banner a').click(function(e) {
                    console.log('Banner clicked');
                    var bannerId = $(this).closest('.random-banner').data('banner-id');
                    console.log('Banner ID:', bannerId);
                    if (bannerId) {
                        console.log('Sending click tracking request');
                        $.post(ajaxurl, {
                            action: 'track_banner_click',
                            banner_id: bannerId,
                            nonce: '<?php echo wp_create_nonce("banner_click"); ?>'
                        }).done(function(response) {
                            console.log('Server response:', response);
                            if (response.success) {
                                console.log('Click tracked successfully');
                            } else {
                                console.log('Click tracking failed:', response.data);
                            }
                        }).fail(function(error) {
                            console.error('AJAX error:', error);
                        });
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Get or create statistics entry for a banner
     * Ensures a statistics entry exists for the given banner
     *
     * @param int $banner_id The ID of the banner
     * @return int The ID of the statistics entry
     */
    private function get_or_create_stat($banner_id) {
        $args = array(
            'post_type' => 'banner_statistic',
            'meta_query' => array(
                array(
                    'key' => 'banner_id',
                    'value' => $banner_id
                )
            ),
            'posts_per_page' => 1
        );

        $existing = get_posts($args);

        if (!empty($existing)) {
            return $existing[0]->ID;
        }

        // Create new statistics entry
        $stat_id = wp_insert_post(array(
            'post_type' => 'banner_statistic',
            'post_title' => get_the_title($banner_id) . ' Statistics',
            'post_status' => 'publish'
        ));

        // Initialize fields
        update_field('banner_id', $banner_id, $stat_id);
        update_field('banner_url', get_field('url', $banner_id), $stat_id);
        update_field('impressions', 0, $stat_id);
        update_field('clicks', 0, $stat_id);
        update_field('ctr', '0.00', $stat_id);
        update_field('price_per_click', '0.00', $stat_id);

        return $stat_id;
    }

    /**
     * Update calculated fields (CTR and Price Per Click)
     * Recalculates and updates CTR and price per click for a statistics entry
     *
     * @param int $stat_id The ID of the statistics entry
     */
    private function update_calculations($stat_id) {
        $impressions = (int)get_field('impressions', $stat_id);
        $clicks = (int)get_field('clicks', $stat_id);
        $banner_id = get_field('banner_id', $stat_id);
        $price = (float)get_field('price', $banner_id);

        // Calculate CTR
        if ($impressions > 0) {
            $ctr = ($clicks / $impressions) * 100;
            update_field('ctr', number_format($ctr, 2), $stat_id);
        }

        // Calculate Price Per Click
        if ($clicks > 0 && $price > 0) {
            $price_per_click = $price / $clicks;
            update_field('price_per_click', number_format($price_per_click, 2), $stat_id);
        }
    }

    /**
     * Add export menu item
     * Adds a submenu item for exporting all statistics
     */
    public function add_export_menu() {
        add_submenu_page(
            'edit.php?post_type=banner_statistic',
            'Export All Statistics',
            'Export All Stats',
            'manage_options',
            'banner-stats-export',
            array($this, 'export_page')
        );
    }

    /**
     * Display export page
     * Renders the page for exporting all banner statistics
     */
    public function export_page() {
        if (isset($_POST['export_stats']) && check_admin_referer('export_banner_stats')) {
            $this->generate_csv();
        }
        ?>
        <div class="wrap">
            <h1>Export All Banner Statistics</h1>
            <p>Export a CSV file containing statistics for all banners with tracking data.</p>
            <form method="post">
                <?php wp_nonce_field('export_banner_stats'); ?>
                <p class="submit">
                    <button type="submit" name="export_stats" class="button button-primary">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                        Export All Statistics
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle single banner statistics export
     * Processes the export request for a single banner's statistics
     */
    public function handle_single_banner_export() {
    // Ensure the request is specifically for exporting a single banner's statistics
    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'export_single_banner_stats' && // Check that the action matches the export request
        isset($_POST['banner_id']) && // Ensure the banner ID is provided
        wp_verify_nonce($_POST['banner_stats_nonce'], 'export_single_banner_stats') // Verify the nonce for security
    ) {
        $banner_id = intval($_POST['banner_id']);

        // Proceed with the export logic
        $args = array(
            'post_type' => 'banner_statistic',
            'meta_query' => array(
                array(
                    'key' => 'banner_id',
                    'value' => $banner_id
                )
            ),
            'posts_per_page' => 1
        );

        $stats = get_posts($args);

        if (!empty($stats)) {
            $stat = $stats[0];

            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="banner-statistics-' . $banner_id . '.csv"');

            $output = fopen('php://output', 'w');

            // Add headers
            fputcsv($output, array('Banner Title', 'URL', 'Impressions', 'Clicks', 'CTR (%)', 'Price Per Click ($)'));

            // Add data
            fputcsv($output, array(
                get_the_title($banner_id),
                get_field('banner_url', $stat->ID),
                number_format(get_field('impressions', $stat->ID)),
                number_format(get_field('clicks', $stat->ID)),
                get_field('ctr', $stat->ID),
                number_format((float)get_field('price_per_click', $stat->ID), 2)
            ));

            fclose($output);
            exit;
        }
    }
}

    /**
     * Generate and download CSV for all statistics
     * Creates a CSV file for all banner statistics and initiates download
     */
    private function generate_csv() {
        // Disable any output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="all-banner-statistics.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output handle
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for proper Excel handling
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add headers
        fputcsv($output, array(
            'Banner Name',
            'URL',
            'Impressions',
            'Clicks',
            'CTR (%)',
            'Price Per Click ($)'
        ));

        // Get all banner statistics
        $args = array(
            'post_type' => 'banner_statistic',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $stats = get_posts($args);

        foreach ($stats as $stat) {
            $banner_id = get_field('banner_id', $stat->ID);
            $impressions = (int)get_field('impressions', $stat->ID);
            $clicks = (int)get_field('clicks', $stat->ID);
            $ctr = get_field('ctr', $stat->ID);
            $price_per_click = get_field('price_per_click', $stat->ID);

            fputcsv($output, array(
                get_the_title($banner_id),
                get_field('banner_url', $stat->ID),
                number_format($impressions),
                number_format($clicks),
                $ctr,
                number_format((float)$price_per_click, 2)
            ));
        }

        fclose($output);
        exit;
    }
}

// Initialize the statistics handler
Banner_Statistics::get_instance();