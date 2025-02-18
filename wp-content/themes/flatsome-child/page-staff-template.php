<?php
/**
 * Template Name: Staff Page
 *
 * Template for displaying Staff Members archive view (/about-us/staff/)
 * Features:
 * - Responsive grid layout
 * - Horizontal division filtering
 * - Handles both image and no-image staff cards
 * - No results messaging
 *
 * @package TeamMembers
 */

get_header();

// Display the UX block as the header
echo do_shortcode('[block id="about-header"]');

// Get the selected category from URL
$selected_category = isset($_GET['staff-category']) ? sanitize_text_field($_GET['staff-category']) : '';

// Set up the query arguments
$args = array(
    'post_type' => 'team-member',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_key' => 'member_type',
    'meta_value' => 'Staff',
    'orderby' => array(
        'menu_order' => 'ASC',
        'title' => 'ASC'
    )
);

// Add taxonomy query if category is selected
if (!empty($selected_category)) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'staff-category',
            'field' => 'slug',
            'terms' => $selected_category
        )
    );
}

$loop = new WP_Query($args);

// Start main content output
$output = '<main id="main" class="">';

/**
 * Flatsome Container Structure
 */
$output .= '<div class="page-wrapper">';
$output .= '<div class="container">';

/**
 * Filter Section
 * Horizontal filter bar at the top
 */
$output .= '<div class="row">';
$output .= '<div class="col large-12 text-center">';
$output .= '<div class="staff-filter-wrapper">';
if (function_exists('get_staff_category_filter')) {
    $output .= get_staff_category_filter();
}
$output .= '</div>'; // End staff-filter-wrapper
$output .= '</div>'; // End col
$output .= '</div>'; // End filter row

/**
 * Main Content Section
 */
$output .= '<div class="row">';
$output .= '<div class="large-12 col">';
$output .= '<div class="col-inner">';

/**
 * Team Members Grid Container
 */
$output .= '<div class="team-members-archive staff-archive">';

// Check if we have staff members to display
if ($loop->have_posts()) {
    $output .= '<div class="row team-members-grid">';

    while ($loop->have_posts()): $loop->the_post();
        $id = get_the_ID();
        $title = get_the_title();
        $link = get_permalink();
        $staff_title = get_field('staff_title', $id);
        $has_image = has_post_thumbnail();

        // Individual Staff Member Card
        $output .= '<div class="col medium-3 small-6 team-member-col">';
        $output .= sprintf(
            '<a href="%s" class="team-member-card %s">',
            esc_url($link),
            $has_image ? 'has-image' : 'no-image'
        );

        if ($has_image) {
            $output .= '<div class="team-member-image">';
            $output .= get_the_post_thumbnail($id, 'team-member-archive', array(
                'class' => 'team-member-archive-thumbnail',
                'loading' => 'lazy'
            ));
            $output .= '</div>';
        }

        $output .= '<div class="team-member-card-content">';
        $output .= sprintf(
            '<span class="team-member-name">%s</span>',
            esc_html($title)
        );

        if ($staff_title) {
            $output .= sprintf(
                '<span class="team-member-title">%s</span>',
                esc_html($staff_title)
            );
        }

        $output .= '</div>'; // End team-member-card-content
        $output .= '</a>'; // End team-member-card
        $output .= '</div>'; // End col
    endwhile;

    $output .= '</div>'; // End team-members-grid

} else {
    // No results found message with customizable classes and back button
    $output .= '<div class="no-staff-found message-box alert-box staff-message staff-message-empty">';
    if (!empty($selected_category)) {
        $category = get_term_by('slug', $selected_category, 'staff-category');
        $category_name = $category ? $category->name : 'this division';
        $output .= '<div class="staff-message-content">';
        $output .= sprintf(
            '<p>No staff members are currently assigned to %s.</p>',
            esc_html($category_name)
        );
        $output .= '<p class="staff-message-action">Please select another division or view all staff.</p>';

        // Add back to staff button
        $output .= sprintf(
            '<div class="staff-message-button"><a href="%s" class="button primary">%s</a></div>',
            esc_url(get_permalink(get_page_by_path('about-us/staff'))),
            esc_html__('Back to All Staff', 'flatsome')
        );

        $output .= '</div>';
    } else {
        $output .= '<div class="staff-message-content">';
        $output .= '<p>No staff members found.</p>';
        $output .= '</div>';
    }
    $output .= '</div>';
}

$output .= '</div>'; // End team-members-archive
$output .= '</div>'; // End col-inner
$output .= '</div>'; // End large-12 col
$output .= '</div>'; // End main content row

$output .= '</div>'; // End container
$output .= '</div>'; // End page-wrapper
$output .= '</main>';

wp_reset_postdata();
echo $output;

get_footer();