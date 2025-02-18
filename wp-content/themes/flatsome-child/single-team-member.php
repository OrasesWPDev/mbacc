<?php
/**
 * Template for displaying single team members
 * 
 * Handles both staff and board of directors single views
 * 
 * @package TeamMembers
 */

/**
 * Format phone number to xxx-xxx-xxxx
 *
 * @param string $phone_number The unformatted phone number
 * @return string The formatted phone number
 */
function format_phone_number($phone_number) {
    // Remove any non-numeric characters
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    // Format the number if it's 10 digits
    if (strlen($phone_number) === 10) {
        return substr($phone_number, 0, 3) . '-' . 
               substr($phone_number, 3, 3) . '-' . 
               substr($phone_number, 6);
    }
    
    // Return original number if not 10 digits
    return $phone_number;
}

get_header();

// Display the UX block as the header
echo do_shortcode('[block id="about-header"]');

if (have_posts()):
    while (have_posts()): the_post();
        $id = get_the_ID();
        $member_type = get_field('member_type', $id);
        
        // Only proceed if member type is valid
        if ($member_type === 'Staff' || $member_type === 'Board of Directors') {
            $title = get_the_title();
            $staff_title = get_field('staff_title', $id);
            
            // Get member type specific fields
            if ($member_type === 'Board of Directors') {
                $company = get_field('company', $id);
                $year_term_ends = get_field('year_term_ends', $id);
            } else {
                $staff_email = get_field('staff_email', $id);
                $staff_phone = get_field('staff_phone', $id);
            }

            $output = '<main id="main" class="">';
            $output .= '<div class="page-wrapper">';
            $output .= '<div class="container">';
            $output .= '<div class="row-large">';
            $output .= '<div class="large-12 col">';
            $output .= '<div class="col-inner">';
            
            $output .= sprintf('<div class="team-member-single %s">', 
                $member_type === 'Board of Directors' ? 'bod-single' : 'staff-single'
            );
            $output .= '<div class="row team-member-single-row">';
            
            // Left column - Image
            $output .= '<div class="col medium-6 team-member-single-image-col">';
            if (has_post_thumbnail()) {
                $output .= '<div class="team-member-image">';
                $output .= get_the_post_thumbnail($id, 'team-member-single', array(
                    'class' => 'team-member-single-thumbnail',
                    'loading' => 'lazy'
                ));
                $output .= '</div>';
            }
            $output .= '</div>';
            
            // Right column - Content
            $output .= '<div class="col medium-6 team-member-single-content-col">';
            $output .= sprintf('<h1 class="team-member-name">%s</h1>', esc_html($title));
            
            if ($staff_title) {
                $output .= sprintf('<h3 class="team-member-title">%s</h3>', esc_html($staff_title));
            }
            
            // Member type specific information
            if ($member_type === 'Board of Directors') {
                if ($company) {
                    $output .= sprintf(
                        '<div class="team-member-company">Where I Work: %s</div>',
                        esc_html($company)
                    );
                }
                if ($year_term_ends) {
                    $output .= sprintf(
                        '<div class="team-member-term">My Term Ends: %s</div>',
                        esc_html($year_term_ends)
                    );
                }
                
                // Bio section without heading for BOD
                $output .= '<div class="team-member-bio">';
                $output .= '<div class="team-member-bio-content">';
                $output .= get_the_content();
                $output .= '</div>';
                $output .= '</div>';
                
            } else {
                // Contact information for staff
                $output .= '<div class="team-member-contact">';
                if ($staff_email) {
                    $output .= sprintf(
                        '<div class="team-member-email"><a href="mailto:%1$s">%1$s</a></div>',
                        esc_attr($staff_email)
                    );
                }
                if ($staff_phone) {
    				$formatted_phone = format_phone_number($staff_phone);
    				$output .= sprintf(
        			'<div class="team-member-phone"><a href="tel:%1$s">%2$s</a></div>',
      				esc_attr($staff_phone), // Keep unformatted for tel: link
        			esc_html($formatted_phone) // Display formatted version
    				);
				}
                $output .= '</div>'; // End team-member-contact
                
                // Bio section with personalized heading for Staff
				if (get_the_content()) { // Only output if there's content
					$output .= '<div class="team-member-bio">';
					$output .= '<div class="team-member-bio-content">';
					$output .= get_the_content();
					$output .= '</div>';
					$output .= '</div>';
				}
            }
            
            $output .= '</div>'; // End right column
            $output .= '</div>'; // End row
            $output .= '</div>'; // End team-member-single
            
            $output .= '</div>'; // End col-inner
            $output .= '</div>'; // End large-12 col
            $output .= '</div>'; // End row-large
            $output .= '</div>'; // End container
            $output .= '</div>'; // End page-wrapper
            $output .= '</main>';
            
            echo $output;
        }
    endwhile;
endif;

get_footer();