<?php
// functions/custom-post-types.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('athlete_dashboard_register_custom_post_types')) {
    function athlete_dashboard_register_custom_post_types() {
        $post_types = apply_filters('athlete_dashboard_custom_post_types', array(
            'workout' => 'Workouts',
            'progress' => 'Progress',
            'squat_progress' => 'Squat Progress',
            'overview' => 'Overview',
            'fitness_plan' => 'Fitness Plan',
            'nutrition' => 'Nutrition',
            'upcoming_workouts' => 'Upcoming Workouts',
            'log_workout' => 'Logged Workouts',
			'meal_log' => 'Meal Logs',
        ));
		foreach ($post_types as $slug => $name) {
			$args = get_post_type_args($slug, $name);
			if ($slug === 'meal_log') {
				$args['capability_type'] = 'meal_log';
				$args['capabilities'] = array(
					'publish_posts' => 'publish_meal_logs',
					'edit_posts' => 'edit_meal_logs',
					'edit_others_posts' => 'edit_others_meal_logs',
					'delete_posts' => 'delete_meal_logs',
					'delete_others_posts' => 'delete_others_meal_logs',
					'read_private_posts' => 'read_private_meal_logs',
					'edit_post' => 'edit_meal_log',
					'delete_post' => 'delete_meal_log',
					'read_post' => 'read_meal_log',
				);
				$args['map_meta_cap'] = true;
			}
			register_post_type($slug, $args);
		}
	}
}

function athlete_dashboard_meal_log_meta_box_callback($post) {
    wp_nonce_field('athlete_dashboard_save_meal_log_data', 'athlete_dashboard_meal_log_nonce');
    $meal_date = get_post_meta($post->ID, '_meal_date', true);
    $meal_type = get_post_meta($post->ID, '_meal_type', true);
    $meal_name = get_post_meta($post->ID, '_meal_name', true);
    $estimated_calories = get_post_meta($post->ID, '_estimated_calories', true);
    $meal_description = get_post_meta($post->ID, '_meal_description', true);

    // New fields
    $protein_type = get_post_meta($post->ID, '_protein_type', true);
    $protein_quantity = get_post_meta($post->ID, '_protein_quantity', true);
    $protein_unit = get_post_meta($post->ID, '_protein_unit', true);
    $fat_type = get_post_meta($post->ID, '_fat_type', true);
    $fat_quantity = get_post_meta($post->ID, '_fat_quantity', true);
    $fat_unit = get_post_meta($post->ID, '_fat_unit', true);
    $carb_starch_type = get_post_meta($post->ID, '_carb_starch_type', true);
    $carb_starch_quantity = get_post_meta($post->ID, '_carb_starch_quantity', true);
    $carb_starch_unit = get_post_meta($post->ID, '_carb_starch_unit', true);
    $carb_fruit_type = get_post_meta($post->ID, '_carb_fruit_type', true);
    $carb_fruit_quantity = get_post_meta($post->ID, '_carb_fruit_quantity', true);
    $carb_fruit_unit = get_post_meta($post->ID, '_carb_fruit_unit', true);
    $carb_vegetable_type = get_post_meta($post->ID, '_carb_vegetable_type', true);
    $carb_vegetable_quantity = get_post_meta($post->ID, '_carb_vegetable_quantity', true);
    $carb_vegetable_unit = get_post_meta($post->ID, '_carb_vegetable_unit', true);
    ?>
    <p>
        <label for="meal_date"><?php _e('Meal Date:', 'athlete-dashboard'); ?></label>
        <input type="date" id="meal_date" name="meal_date" value="<?php echo esc_attr($meal_date); ?>" />
    </p>
    <p>
        <label for="meal_type"><?php _e('Meal Type:', 'athlete-dashboard'); ?></label>
        <select id="meal_type" name="meal_type">
            <option value="breakfast" <?php selected($meal_type, 'breakfast'); ?>><?php _e('Breakfast', 'athlete-dashboard'); ?></option>
            <option value="lunch" <?php selected($meal_type, 'lunch'); ?>><?php _e('Lunch', 'athlete-dashboard'); ?></option>
            <option value="dinner" <?php selected($meal_type, 'dinner'); ?>><?php _e('Dinner', 'athlete-dashboard'); ?></option>
            <option value="snack" <?php selected($meal_type, 'snack'); ?>><?php _e('Snack', 'athlete-dashboard'); ?></option>
        </select>
    </p>
    <p>
        <label for="meal_name"><?php _e('Meal Name:', 'athlete-dashboard'); ?></label>
        <input type="text" id="meal_name" name="meal_name" value="<?php echo esc_attr($meal_name); ?>" />
    </p>
    <!-- Protein -->
    <p>
        <label for="protein_type"><?php _e('Protein Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="protein_type" name="protein_type" value="<?php echo esc_attr($protein_type); ?>" />
    </p>
    <p>
        <label for="protein_quantity"><?php _e('Protein Quantity:', 'athlete-dashboard'); ?></label>
        <input type="number" id="protein_quantity" name="protein_quantity" value="<?php echo esc_attr($protein_quantity); ?>" step="0.1" />
        <select id="protein_unit" name="protein_unit">
            <option value="g" <?php selected($protein_unit, 'g'); ?>>g</option>
            <option value="oz" <?php selected($protein_unit, 'oz'); ?>>oz</option>
            <option value="pieces" <?php selected($protein_unit, 'pieces'); ?>>pieces</option>
        </select>
    </p>
    <!-- Fat -->
    <p>
        <label for="fat_type"><?php _e('Fat Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="fat_type" name="fat_type" value="<?php echo esc_attr($fat_type); ?>" />
    </p>
    <p>
        <label for="fat_quantity"><?php _e('Fat Quantity:', 'athlete-dashboard'); ?></label>
        <input type="number" id="fat_quantity" name="fat_quantity" value="<?php echo esc_attr($fat_quantity); ?>" step="0.1" />
        <select id="fat_unit" name="fat_unit">
            <option value="tsp" <?php selected($fat_unit, 'tsp'); ?>>tsp</option>
            <option value="tbsp" <?php selected($fat_unit, 'tbsp'); ?>>tbsp</option>
            <option value="g" <?php selected($fat_unit, 'g'); ?>>g</option>
        </select>
    </p>
    <!-- Carbohydrates: Starches & Grains -->
    <p>
        <label for="carb_starch_type"><?php _e('Starch/Grain Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="carb_starch_type" name="carb_starch_type" value="<?php echo esc_attr($carb_starch_type); ?>" />
    </p>
    <p>
        <label for="carb_starch_quantity"><?php _e('Starch/Grain Quantity:', 'athlete-dashboard'); ?></label>
        <input type="number" id="carb_starch_quantity" name="carb_starch_quantity" value="<?php echo esc_attr($carb_starch_quantity); ?>" step="0.1" />
        <select id="carb_starch_unit" name="carb_starch_unit">
            <option value="g" <?php selected($carb_starch_unit, 'g'); ?>>g</option>
            <option value="oz" <?php selected($carb_starch_unit, 'oz'); ?>>oz</option>
            <option value="cups" <?php selected($carb_starch_unit, 'cups'); ?>>cups</option>
            <option value="slices" <?php selected($carb_starch_unit, 'slices'); ?>>slices</option>
        </select>
    </p>
    <!-- Carbohydrates: Fruits -->
    <p>
        <label for="carb_fruit_type"><?php _e('Fruit Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="carb_fruit_type" name="carb_fruit_type" value="<?php echo esc_attr($carb_fruit_type); ?>" />
    </p>
    <p>
        <label for="carb_fruit_quantity"><?php _e('Fruit Quantity:', 'athlete-dashboard'); ?></label>
        <input type="number" id="carb_fruit_quantity" name="carb_fruit_quantity" value="<?php echo esc_attr($carb_fruit_quantity); ?>" step="0.1" />
        <select id="carb_fruit_unit" name="carb_fruit_unit">
            <option value="pieces" <?php selected($carb_fruit_unit, 'pieces'); ?>>pieces</option>
            <option value="g" <?php selected($carb_fruit_unit, 'g'); ?>>g</option>
            <option value="oz" <?php selected($carb_fruit_unit, 'oz'); ?>>oz</option>
            <option value="cups" <?php selected($carb_fruit_unit, 'cups'); ?>>cups</option>
        </select>
    </p>
    <!-- Carbohydrates: Vegetables -->
    <p>
        <label for="carb_vegetable_type"><?php _e('Vegetable Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="carb_vegetable_type" name="carb_vegetable_type" value="<?php echo esc_attr($carb_vegetable_type); ?>" />
    </p>
    <p>
        <label for="carb_vegetable_quantity"><?php _e('Vegetable Quantity:', 'athlete-dashboard'); ?></label>
        <input type="number" id="carb_vegetable_quantity" name="carb_vegetable_quantity" value="<?php echo esc_attr($carb_vegetable_quantity); ?>" step="0.1" />
        <select id="carb_vegetable_unit" name="carb_vegetable_unit">
            <option value="g" <?php selected($carb_vegetable_unit, 'g'); ?>>g</option>
            <option value="oz" <?php selected($carb_vegetable_unit, 'oz'); ?>>oz</option>
            <option value="cups" <?php selected($carb_vegetable_unit, 'cups'); ?>>cups</option>
        </select>
    </p>
    <p>
        <label for="estimated_calories"><?php _e('Estimated Calories:', 'athlete-dashboard'); ?></label>
        <input type="number" id="estimated_calories" name="estimated_calories" value="<?php echo esc_attr($estimated_calories); ?>" />
    </p>
    <p>
        <label for="meal_description"><?php _e('Meal Description:', 'athlete-dashboard'); ?></label>
        <textarea id="meal_description" name="meal_description"><?php echo esc_textarea($meal_description); ?></textarea>
    </p>
    <?php
}

function athlete_dashboard_save_meal_log_data($post_id) {
    if (!isset($_POST['athlete_dashboard_meal_log_nonce']) || !wp_verify_nonce($_POST['athlete_dashboard_meal_log_nonce'], 'athlete_dashboard_save_meal_log_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $fields = array(
        'meal_date', 'meal_type', 'meal_name', 'estimated_calories', 'meal_description',
        'protein_type', 'protein_quantity', 'protein_unit',
        'fat_type', 'fat_quantity', 'fat_unit',
        'carb_starch_type', 'carb_starch_quantity', 'carb_starch_unit',
        'carb_fruit_type', 'carb_fruit_quantity', 'carb_fruit_unit',
        'carb_vegetable_type', 'carb_vegetable_quantity', 'carb_vegetable_unit'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post_meal_log', 'athlete_dashboard_save_meal_log_data');

/**
 * Grant meal log capabilities to subscribers.
 */
function athlete_dashboard_grant_meal_log_capabilities() {
    $subscriber = get_role('subscriber');
    $capabilities = array(
        'publish_meal_logs',
        'edit_meal_logs',
        'delete_meal_logs',
        'read_meal_logs',
    );

    foreach ($capabilities as $cap) {
        $subscriber->add_cap($cap);
    }
}
add_action('init', 'athlete_dashboard_grant_meal_log_capabilities');


function get_log_workout_post_type_args($name) {
    $labels = array(
        'name'               => _x($name, 'post type general name', 'athlete-dashboard'),
        'singular_name'      => _x(rtrim($name, 's'), 'post type singular name', 'athlete-dashboard'),
        'menu_name'          => _x($name, 'admin menu', 'athlete-dashboard'),
        'name_admin_bar'     => _x(rtrim($name, 's'), 'add new on admin bar', 'athlete-dashboard'),
        'add_new'            => _x('Add New', 'log_workout', 'athlete-dashboard'),
        'add_new_item'       => __('Add New ' . rtrim($name, 's'), 'athlete-dashboard'),
        'new_item'           => __('New ' . rtrim($name, 's'), 'athlete-dashboard'),
        'edit_item'          => __('Edit ' . rtrim($name, 's'), 'athlete-dashboard'),
        'view_item'          => __('View ' . rtrim($name, 's'), 'athlete-dashboard'),
        'all_items'          => __('All ' . $name, 'athlete-dashboard'),
        'search_items'       => __('Search ' . $name, 'athlete-dashboard'),
        'parent_item_colon'  => __('Parent ' . rtrim($name, 's') . ':', 'athlete-dashboard'),
        'not_found'          => __('No ' . strtolower($name) . ' found.', 'athlete-dashboard'),
        'not_found_in_trash' => __('No ' . strtolower($name) . ' found in Trash.', 'athlete-dashboard')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'workout-log'),
        'capability_type'    => 'log_workout',
        'capabilities'       => array(
            'publish_posts'       => 'publish_log_workouts',
            'edit_posts'          => 'edit_log_workouts',
            'edit_others_posts'   => 'edit_others_log_workouts',
            'delete_posts'        => 'delete_log_workouts',
            'delete_others_posts' => 'delete_others_log_workouts',
            'read_private_posts'  => 'read_private_log_workouts',
            'edit_post'           => 'edit_log_workout',
            'delete_post'         => 'delete_log_workout',
            'read_post'           => 'read_log_workout',
        ),
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author'),
        'show_in_rest'       => true,
    );

    return $args;
}

function athlete_dashboard_add_workout_log_meta_boxes() {
    add_meta_box(
        'workout_log_details',
        __('Workout Log Details', 'athlete-dashboard'),
        'athlete_dashboard_workout_log_meta_box_callback',
        'log_workout',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'athlete_dashboard_add_workout_log_meta_boxes');

function athlete_dashboard_workout_log_meta_box_callback($post) {
    wp_nonce_field('athlete_dashboard_save_workout_log_data', 'athlete_dashboard_workout_log_nonce');
    $workout_date = get_post_meta($post->ID, '_workout_date', true);
    $workout_duration = get_post_meta($post->ID, '_workout_duration', true);
    $workout_type = get_post_meta($post->ID, '_workout_type', true);
    $workout_intensity = get_post_meta($post->ID, '_workout_intensity', true);
    ?>
    <p>
        <label for="workout_date"><?php _e('Workout Date:', 'athlete-dashboard'); ?></label>
        <input type="date" id="workout_date" name="workout_date" value="<?php echo esc_attr($workout_date); ?>" />
    </p>
    <p>
        <label for="workout_duration"><?php _e('Duration (minutes):', 'athlete-dashboard'); ?></label>
        <input type="number" id="workout_duration" name="workout_duration" value="<?php echo esc_attr($workout_duration); ?>" />
    </p>
    <p>
        <label for="workout_type"><?php _e('Workout Type:', 'athlete-dashboard'); ?></label>
        <input type="text" id="workout_type" name="workout_type" value="<?php echo esc_attr($workout_type); ?>" />
    </p>
    <p>
        <label for="workout_intensity"><?php _e('Intensity (1-10):', 'athlete-dashboard'); ?></label>
        <input type="number" id="workout_intensity" name="workout_intensity" min="1" max="10" value="<?php echo esc_attr($workout_intensity); ?>" />
    </p>
    <?php
}

function athlete_dashboard_save_workout_log_data($post_id) {
    if (!isset($_POST['athlete_dashboard_workout_log_nonce']) || !wp_verify_nonce($_POST['athlete_dashboard_workout_log_nonce'], 'athlete_dashboard_save_workout_log_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_log_workout', $post_id)) {
        return;
    }
    
    $fields = array('workout_date', 'workout_duration', 'workout_type', 'workout_intensity');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post_log_workout', 'athlete_dashboard_save_workout_log_data');

if (!function_exists('get_post_type_args')) {
    function get_post_type_args($slug, $name) {
        $labels = array(
            'name'               => _x($name, 'post type general name', 'athlete-dashboard'),
            'singular_name'      => _x(rtrim($name, 's'), 'post type singular name', 'athlete-dashboard'),
            'menu_name'          => _x($name, 'admin menu', 'athlete-dashboard'),
            'name_admin_bar'     => _x(rtrim($name, 's'), 'add new on admin bar', 'athlete-dashboard'),
            'add_new'            => _x('Add New', $slug, 'athlete-dashboard'),
            'add_new_item'       => __('Add New ' . rtrim($name, 's'), 'athlete-dashboard'),
            'new_item'           => __('New ' . rtrim($name, 's'), 'athlete-dashboard'),
            'edit_item'          => __('Edit ' . rtrim($name, 's'), 'athlete-dashboard'),
            'view_item'          => __('View ' . rtrim($name, 's'), 'athlete-dashboard'),
            'all_items'          => __('All ' . $name, 'athlete-dashboard'),
            'search_items'       => __('Search ' . $name, 'athlete-dashboard'),
            'parent_item_colon'  => __('Parent ' . rtrim($name, 's') . ':', 'athlete-dashboard'),
            'not_found'          => __('No ' . strtolower($name) . ' found.', 'athlete-dashboard'),
            'not_found_in_trash' => __('No ' . strtolower($name) . ' found in Trash.', 'athlete-dashboard')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => $slug),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail'),
            'show_in_rest'       => true,
        );

        return $args;
    }
}

add_action('init', 'athlete_dashboard_register_custom_post_types');

/**
 * Grant necessary capabilities to subscribers for managing their own workout logs.
 * This function is hooked to 'init' and runs during WordPress initialization.
 */
function athlete_dashboard_grant_workout_log_capabilities() {
    $subscriber = get_role('subscriber');
    if (!$subscriber) {
        return;
    }

    $capabilities = array(
        'publish_log_workouts',
        'edit_log_workouts',
        'delete_log_workouts',
        'read_log_workouts',
    );

    foreach ($capabilities as $cap) {
        if (!$subscriber->has_cap($cap)) {
            $subscriber->add_cap($cap);
        }
    }
}
add_action('init', 'athlete_dashboard_grant_workout_log_capabilities');