<?php
/**
 * Template Name: Board of Directors Page
 *
 * Template for displaying Board of Directors archive view (/about-us/board-of-directors/)
 * Features:
 * - Responsive grid layout
 * - Handles both image and no-image cards
 * - Company name display
 *
 * @package TeamMembers
 */

get_header();

// Display the UX block as the header
echo do_shortcode('[block id="about-header"]');

// Verify we're showing Board of Directors members
$member_type = 'Board of Directors';
$loop = get_team_members($member_type);

if ($loop->have_posts()):
    $output = '<main id="main" class="">';

    /**
     * Flatsome Container Structure
     * Maintains consistent spacing and alignment with theme
     */
    $output .= '<div class="page-wrapper">';
    $output .= '<div class="container">';
    $output .= '<div class="row-large">';
    $output .= '<div class="large-12 col">';
    $output .= '<div class="col-inner">';
    
    /**
     * Board Members Grid Container
     * Creates responsive grid for board member cards
     */
    $output .= '<div class="team-members-archive bod-archive">';
    $output .= '<div class="row team-members-grid">';

    /**
     * Loop through each board member
     * Generate individual board member cards
     */
    while ($loop->have_posts()): $loop->the_post();
        // Verify each member is a Board of Directors member
        $current_member_type = get_field('member_type');
        if ($current_member_type === $member_type) {
            // Get board member details
            $id = get_the_ID();
            $title = get_the_title();
            $link = get_permalink();
            $company = get_field('company', $id);
            $has_image = has_post_thumbnail();

            /**
             * Individual Board Member Card
             * Column sizes:
             * - Desktop: 3 columns (medium-3)
             * - Mobile: 2 columns (small-6)
             */
            $output .= '<div class="col medium-3 small-6 team-member-col">';
            
            // Card wrapper with conditional classes for image/no-image styling
            $output .= sprintf(
                '<a href="%s" class="team-member-card %s">',
                esc_url($link),
                $has_image ? 'has-image' : 'no-image'
            );

            /**
             * Board Member Image
             * Only output if thumbnail exists
             */
            if ($has_image) {
                $output .= '<div class="team-member-image">';
                $output .= get_the_post_thumbnail($id, 'team-member-archive', array(
                    'class' => 'team-member-archive-thumbnail',
                    'loading' => 'lazy'
                ));
                $output .= '</div>';
            }

            /**
             * Board Member Details
             * Always displays name
             * Conditionally displays company if available
             */
            $output .= '<div class="team-member-card-content">';
            
            // Board member name
            $output .= sprintf(
                '<span class="team-member-name">%s</span>',
                esc_html($title)
            );

            // Company name
            if ($company) {
                $output .= sprintf(
                    '<span class="team-member-company">%s</span>',
                    esc_html($company)
                );
            }

            $output .= '</div>'; // End team-member-card-content
            $output .= '</a>'; // End team-member-card
            $output .= '</div>'; // End col
        }
    endwhile;

    /**
     * Close all container divs
     * Maintain proper nesting structure
     */
    $output .= '</div>'; // End team-members-grid
    $output .= '</div>'; // End team-members-archive
    $output .= '</div>'; // End col-inner
    $output .= '</div>'; // End large-12 col
    $output .= '</div>'; // End row-large
    $output .= '</div>'; // End container
    $output .= '</div>'; // End page-wrapper
    $output .= '</main>';

    // Reset WordPress post data
    wp_reset_postdata();
    
    // Output the completed HTML
    echo $output;
endif;

get_footer();