<?php
/**
 * Custom Tag Dropdown Widget
 *
 * Creates a dropdown widget for tags that matches Flatsome's category dropdown styling
 * Features:
 * - Matches Flatsome theme styling
 * - Creates dropdown of all available tags
 * - Includes divider line
 * - Maintains accessibility standards
 */
class Tag_Dropdown_Widget extends WP_Widget {
    /**
     * Widget Constructor
     * Sets up the widget name and description
     */
    public function __construct() {
        parent::__construct(
            'tag_dropdown_widget', // Base ID
            'Tags',               // Widget name in admin
            array('description' => 'A dropdown list of tags that matches Flatsome category styling')
        );
    }

    /**
     * Frontend Display of Widget
     *
     * @param array $args     Widget arguments
     * @param array $instance Saved values from database
     */
    public function widget($args, $instance) {
        // Get the widget title
        $title = apply_filters('widget_title', $instance['title']);

        // Start widget wrapper
        echo $args['before_widget'];

        // Widget Title Structure - Matches Flatsome Category Widget
        echo '<span class="widget-title">';
        echo '<span>' . ($title ? esc_html($title) : 'Tags') . '</span>';
        echo '</span>';

        // Flatsome Divider Line
        echo '<div class="is-divider small"></div>';

        // Get all tags, sorted alphabetically
        $tags = get_tags(array('orderby' => 'name'));

        if ($tags) {
            // Start dropdown form structure
            echo '<form action="' . esc_url(home_url('/')) . '" method="get">';

            // Accessible label for screen readers
            echo '<label class="screen-reader-text" for="tag-dropdown">Tags</label>';

            // Create dropdown with Flatsome styling
            echo '<select name="tag" id="tag-dropdown" class="postform">';

            // Default option
            echo '<option value="-1">Select Tag</option>';

            // List all tags as options
            foreach ($tags as $tag) {
                echo '<option class="level-0" value="' . esc_attr($tag->slug) . '">';
                echo esc_html($tag->name);
                echo '</option>';
            }

            echo '</select>';
            echo '</form>';
        }

        // Close widget wrapper
        echo $args['after_widget'];
    }

    /**
     * Backend Widget Form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        // Get the saved title or use default
        $title = isset($instance['title']) ? $instance['title'] : 'Tags';

        // Create the form field for widget title
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
     * Sanitize Widget Form Values Before Save
     *
     * @param array $new_instance Values just sent to be saved
     * @param array $old_instance Previously saved values from database
     * @return array Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }
}

/**
 * Register Tag Dropdown Widget
 * Hooks into WordPress widget initialization
 */
function register_tag_dropdown_widget() {
    register_widget('Tag_Dropdown_Widget');
}
add_action('widgets_init', 'register_tag_dropdown_widget');