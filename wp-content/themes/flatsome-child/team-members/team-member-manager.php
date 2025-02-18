<?php
/**
 * Team Member Manager
 *
 * Handles the functionality for displaying team members (Staff and Board of Directors)
 * including custom URL structure and rewrite rules.
 *
 * @package TeamMembers
 */

/**
 * Query team members based on member type
 *
 * Retrieves team members from the database, filtered by member type
 * and ordered by menu order (page attributes).
 *
 * @param string $member_type The type of team member ('Staff' or 'Board of Directors')
 * @return WP_Query Query results containing filtered team members
 */
function get_team_members($member_type) {
    // Set up arguments for team member query
    $args = array(
        'post_type'      => 'team-member',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => 'member_type',
        'meta_value'     => $member_type,
        'orderby'        => array(
            'menu_order' => 'ASC',
            'title'     => 'ASC'
        )
    );

    return new WP_Query($args);
}

/**
 * Add custom rewrite rules for team members
 *
 * Creates URL structure for:
 * - Staff: /about-us/staff/[member-name]
 * - Board of Directors: /about-us/board-of-directors/[member-name]
 */
function add_team_member_rewrite_rules() {
    add_rewrite_rule(
        'about-us/staff/([^/]+)/?$',
        'index.php?team-member=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        'about-us/board-of-directors/([^/]+)/?$',
        'index.php?team-member=$matches[1]',
        'top'
    );
}
add_action('init', 'add_team_member_rewrite_rules');

/**
 * Modify the permalink for team members
 *
 * @param string $url The default URL
 * @param object $post The post object
 * @return string Modified URL based on member type
 */
function modify_team_member_link($url, $post) {
    if ($post->post_type === 'team-member') {
        $member_type = get_field('member_type', $post->ID);

        if ($member_type === 'Staff') {
            return home_url('about-us/staff/' . $post->post_name);
        } elseif ($member_type === 'Board of Directors') {
            return home_url('about-us/board-of-directors/' . $post->post_name);
        }
    }
    return $url;
}
add_filter('post_type_link', 'modify_team_member_link', 10, 2);

/**
 * Flush rewrite rules
 *
 * This function should be called only when:
 * - The plugin is activated
 * - The permalink structure is changed
 * - The rewrite rules are modified
 */
function flush_team_member_rules() {
    add_team_member_rewrite_rules();
    flush_rewrite_rules();
}


/**
 * Add Member Type column to team members admin list
 *
 * @param array $columns Current admin columns
 * @return array Modified columns
 */
function add_team_member_type_column($columns) {
    $new_columns = array();

    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns[$key] = $value;
            $new_columns['member_type'] = 'Member Type';
        } else {
            $new_columns[$key] = $value;
        }
    }

    return $new_columns;
}
add_filter('manage_team-member_posts_columns', 'add_team_member_type_column');

/**
 * Display Member Type value in the custom column
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function display_team_member_type_column($column, $post_id) {
    if ($column === 'member_type') {
        $member_type = get_field('member_type', $post_id);
        echo esc_html($member_type);
    }
}
add_action('manage_team-member_posts_custom_column', 'display_team_member_type_column', 10, 2);

/**
 * Make Member Type column sortable
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function make_member_type_column_sortable($columns) {
    $columns['member_type'] = 'member_type';
    return $columns;
}
add_filter('manage_edit-team-member_sortable_columns', 'make_member_type_column_sortable');

/**
 * Modify the Member Type field to be a dropdown in Quick Edit
 */
function modify_member_type_field() {
    // Change the field type to select
    add_filter('acf/load_field/name=member_type', function($field) {
        $field['type'] = 'select';
        $field['choices'] = array(
            'Staff' => 'Staff',
            'Board of Directors' => 'Board of Directors'
        );
        $field['ui'] = 1; // Enhanced select interface
        $field['required'] = 1;
        $field['default_value'] = 'Staff';
        return $field;
    });
}
add_action('acf/init', 'modify_member_type_field');

/**
 * Add Member Type filter dropdown to admin list
 *
 * @param string $post_type The current post type
 */
function add_member_type_filter_dropdown($post_type) {
    if ($post_type !== 'team-member') {
        return;
    }

    $selected = isset($_GET['member_type_filter']) ? $_GET['member_type_filter'] : '';

    echo '<select name="member_type_filter">';
    echo '<option value="">All Member Types</option>';
    echo sprintf(
        '<option value="Staff" %s>Staff</option>',
        selected($selected, 'Staff', false)
    );
    echo sprintf(
        '<option value="Board of Directors" %s>Board of Directors</option>',
        selected($selected, 'Board of Directors', false)
    );
    echo '</select>';
}
add_action('restrict_manage_posts', 'add_member_type_filter_dropdown');

/**
 * Filter the query based on the selected member type
 *
 * @param WP_Query $query The main query
 */
function filter_team_members_by_type($query) {
    global $pagenow;

    if (!(is_admin()
        && $pagenow === 'edit.php'
        && isset($_GET['post_type'])
        && $_GET['post_type'] === 'team-member'
        && isset($_GET['member_type_filter'])
        && !empty($_GET['member_type_filter']))) {
        return;
    }

    $query->query_vars['meta_key'] = 'member_type';
    $query->query_vars['meta_value'] = $_GET['member_type_filter'];
}
add_action('pre_get_posts', 'filter_team_members_by_type');


/**
 * Customize Yoast SEO Breadcrumbs for Team Members
 * 
 * Modifies the breadcrumb trail to include 'About Us' and the specific member type
 * (Staff or Board of Directors) for single team member views.
 * 
 * @param array $links The current breadcrumb links
 * @return array Modified breadcrumb links
 */
function custom_wpseo_breadcrumb_links($links) {
    if (is_singular('team-member')) {
        $member_type = get_field('member_type');
        
        // Add 'About Us' link
        $breadcrumb_about_us = array(
            'url' => home_url('/about-us/'),
            'text' => 'About Us',
        );

        // Add 'Staff' or 'Board of Directors' link based on member type
        if ($member_type === 'Staff') {
            $breadcrumb_member_type = array(
                'url' => home_url('/about-us/staff/'),
                'text' => 'Staff',
            );
        } elseif ($member_type === 'Board of Directors') {
            $breadcrumb_member_type = array(
                'url' => home_url('/about-us/board-of-directors/'),
                'text' => 'Board of Directors',
            );
        }

        // Insert the new breadcrumbs after the home link
        array_splice($links, 1, 0, array($breadcrumb_about_us, $breadcrumb_member_type));
    }

    return $links;
}
add_filter('wpseo_breadcrumb_links', 'custom_wpseo_breadcrumb_links');

function register_team_member_image_sizes() {
    // Archive pages size
    add_image_size('team-member-archive', 281, 300, true);
    // Single page size - update/add this line
    add_image_size('team-member-single', 422, 350, true);
}
add_action('after_setup_theme', 'register_team_member_image_sizes');

/**
 * Generate Staff Category Filter Widget
 * Matches Flatsome native category widget styling
 * Dynamically pulls categories from WordPress admin
 *
 * @return string HTML output for the filter widget
 */
function get_staff_category_filter() {
    $staff_categories = get_terms(array(
        'taxonomy' => 'staff-category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    if (!is_wp_error($staff_categories) && !empty($staff_categories)) {
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));

        $output = '<aside id="staff-categories" class="widget widget_categories">';
        $output .= '<span class="widget-title "><span>Filter by Divisions</span></span>';
        $output .= '<div class="is-divider small"></div>';

        $output .= '<form action="' . esc_url($current_url) . '" method="get">';

        // Add a wrapper div for better dropdown control
        $output .= '<div class="staff-category-select-wrapper">';
        $output .= '<select name="staff-category" class="postform" onchange="this.form.submit()">';
        $output .= '<option value="">All Staff</option>';

        $current = isset($_GET['staff-category']) ? sanitize_text_field($_GET['staff-category']) : '';

        foreach ($staff_categories as $category) {
            $selected = ($current === $category->slug) ? ' selected="selected"' : '';
            $output .= sprintf(
                '<option class="level-0" value="%s"%s>%s</option>',
                esc_attr($category->slug),
                $selected,
                esc_html($category->name)
            );
        }

        $output .= '</select>';
        $output .= '</div>'; // End staff-category-select-wrapper
        $output .= '</form>';
        $output .= '</aside>';

        return $output;
    }

    return '';
}