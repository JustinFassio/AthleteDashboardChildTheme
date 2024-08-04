<?php
/**
 * User Profile Functions for Athlete Dashboard
 *
 * This file contains functions related to user profiles, including
 * custom fields, avatar handling, and profile updates.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Grant upload capability to subscribers.
 */
function grant_upload_capability_to_subscribers() {
    $subscriber_role = get_role('subscriber');
    if ($subscriber_role) {
        $subscriber_role->add_cap('upload_files');
    }
}
add_action('init', 'grant_upload_capability_to_subscribers');

/**
 * Handle profile update via AJAX.
 */
function handle_profile_update() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athlete_dashboard_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    $user_id = get_current_user_id();
    $user_data = array(
        'ID' => $user_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email' => sanitize_email($_POST['email']),
        'description' => sanitize_textarea_field($_POST['bio'])
    );
    $result = wp_update_user($user_data);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => 'Profile updated successfully']);
    }
}
add_action('wp_ajax_update_profile', 'handle_profile_update');

/**
 * Get custom avatar for users.
 *
 * @param string $avatar HTML string for the user's avatar.
 * @param mixed $id_or_email A user ID, email address, or comment object.
 * @param int $size Size of the avatar image.
 * @param string $default URL to a default avatar image.
 * @param string $alt Alternative text to use in the avatar image tag.
 * @return string HTML for the user's avatar.
 */
function get_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $user = false;
    if (is_numeric($id_or_email)) {
        $id = (int) $id_or_email;
        $user = get_user_by('id', $id);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by('id', $id);
        }
    } else {
        $user = get_user_by('email', $id_or_email);
    }
    if ($user && is_object($user)) {
        $avatar_id = get_user_meta($user->ID, 'wp_user_avatar', true);
        if ($avatar_id) {
            $avatar_url = wp_get_attachment_image_src($avatar_id, 'thumbnail');
            if ($avatar_url) {
                $avatar = "<img alt='" . esc_attr($alt) . "' src='" . esc_url($avatar_url[0]) . "' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
            }
        }
    }
    return $avatar;
}
add_filter('get_avatar', 'get_custom_avatar', 10, 5);

/**
 * Add custom fields to user profile.
 *
 * @param WP_User $user User object.
 */
function add_custom_user_profile_fields($user) {
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }
    error_log("Adding custom user profile fields for user ID: " . $user->ID);
    ?>
    <h3><?php esc_html_e("Exercise Test Results", "athlete-dashboard"); ?></h3>
    <table class="form-table">
        <?php
        $exercise_tests = apply_filters('athlete_dashboard_exercise_tests', athlete_dashboard_get_exercise_tests());
        error_log("Exercise tests: " . print_r($exercise_tests, true));

        foreach ($exercise_tests as $field_name => $test) :
            $field_value = get_user_meta($user->ID, $field_name, true);
            $is_bilateral = isset($test['bilateral']) && $test['bilateral'];
            
            error_log("Processing field: $field_name");
            error_log("Is bilateral: " . ($is_bilateral ? "Yes" : "No"));
            error_log("Raw field value: " . print_r($field_value, true));
            
            $field_args = apply_filters('athlete_dashboard_custom_field_args', array(
                'label' => $test['label'],
                'unit' => $test['unit'],
                'value' => $field_value,
                'readonly' => in_array($field_name, ['body_weight', 'squat_progress', 'deadlift_progress']),
                'bilateral' => $is_bilateral
            ), $field_name);
            
            error_log("Processed field args: " . print_r($field_args, true));
            
            ?>
            <tr>
                <th><label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($field_args['label'] . ' (' . $field_args['unit'] . ')'); ?></label></th>
                <td>
                    <?php if ($field_args['bilateral']): ?>
                        <label for="<?php echo esc_attr($field_name . '_left'); ?>"><?php esc_html_e('Left', 'athlete-dashboard'); ?></label>
                        <input type="text" name="<?php echo esc_attr($field_name . '_left'); ?>" id="<?php echo esc_attr($field_name . '_left'); ?>" value="<?php echo esc_attr($field_args['value']['left'] ?? ''); ?>" class="regular-text" <?php echo $field_args['readonly'] ? 'readonly' : ''; ?> />
                        <br>
                        <label for="<?php echo esc_attr($field_name . '_right'); ?>"><?php esc_html_e('Right', 'athlete-dashboard'); ?></label>
                        <input type="text" name="<?php echo esc_attr($field_name . '_right'); ?>" id="<?php echo esc_attr($field_name . '_right'); ?>" value="<?php echo esc_attr($field_args['value']['right'] ?? ''); ?>" class="regular-text" <?php echo $field_args['readonly'] ? 'readonly' : ''; ?> />
                    <?php else: ?>
                        <input type="text" name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($field_args['value']); ?>" class="regular-text" <?php echo $field_args['readonly'] ? 'readonly' : ''; ?> />
                    <?php endif; ?>
                    <br />
                    <span class="description">
                        <?php echo $field_args['readonly'] 
                            ? esc_html__("This field is automatically updated with your most recent entry.", "athlete-dashboard")
                            : esc_html__("Enter your most recent result for this test.", "athlete-dashboard"); 
                        ?>
                    </span>
                </td>
            </tr>
            <?php
            error_log("Rendered field: $field_name");
        endforeach;
        ?>
    </table>
    <?php
    error_log("Finished adding custom user profile fields for user ID: " . $user->ID);
}
add_action('show_user_profile', 'add_custom_user_profile_fields');
add_action('edit_user_profile', 'add_custom_user_profile_fields');

/**
 * Save custom user profile fields.
 *
 * @param int $user_id ID of the user being saved.
 * @return bool|void False if current user cannot edit the given user.
 */
function save_custom_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    error_log("Saving custom user profile fields for user ID: $user_id");
    $exercise_tests = athlete_dashboard_get_exercise_tests();
    foreach ($exercise_tests as $field_name => $test) {
        if ($test['bilateral']) {
            $left_value = isset($_POST[$field_name . '_left']) ? sanitize_text_field($_POST[$field_name . '_left']) : '';
            $right_value = isset($_POST[$field_name . '_right']) ? sanitize_text_field($_POST[$field_name . '_right']) : '';
            $value = ['left' => $left_value, 'right' => $right_value];
            $update_result = update_user_meta($user_id, $field_name, $value);
            error_log("Attempted to update bilateral field $field_name for user $user_id with values: " . print_r($value, true));
            error_log("Update result: " . ($update_result ? "Success" : "Failure"));
        } else {
            if (isset($_POST[$field_name])) {
                $value = sanitize_text_field($_POST[$field_name]);
                $update_result = update_user_meta($user_id, $field_name, $value);
                error_log("Attempted to update field $field_name for user $user_id with value: $value");
                error_log("Update result: " . ($update_result ? "Success" : "Failure"));
            }
        }
    }
}
add_action('personal_options_update', 'save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'save_custom_user_profile_fields');

function check_bilateral_field_data($user_id) {
    $exercise_tests = athlete_dashboard_get_exercise_tests();
    foreach ($exercise_tests as $field_name => $test) {
        if ($test['bilateral']) {
            $value = get_user_meta($user_id, $field_name, true);
            error_log("Bilateral field $field_name for user $user_id: " . print_r($value, true));
        }
    }
}

// Call this function after saving user profile
add_action('profile_update', 'check_bilateral_field_data', 10, 1);

/**
 * Get the most recent weight entry for a user.
 *
 * @param int $user_id ID of the user.
 * @return string The most recent weight entry or 'No data' if not found.
 */
function get_most_recent_weight($user_id) {
    $weight_progress = get_user_meta($user_id, 'weight_progress', true);
    error_log('Weight Progress for user ' . $user_id . ': ' . print_r($weight_progress, true));
    
    if (!is_array($weight_progress) || empty($weight_progress)) {
        error_log('No valid weight data found for user ' . $user_id);
        return 'No data';
    }
    
    $latest_entry = reset($weight_progress);
    error_log('Latest weight entry: ' . print_r($latest_entry, true));
    
    if (!isset($latest_entry['weight']) || !isset($latest_entry['unit'])) {
        error_log('Invalid weight data structure for user ' . $user_id . '. Keys found: ' . implode(', ', array_keys($latest_entry)));
        return 'Invalid data (structure)';
    }
    
    if (!is_numeric($latest_entry['weight'])) {
        error_log('Invalid weight value for user ' . $user_id . '. Value: ' . $latest_entry['weight']);
        return 'Invalid data (value)';
    }
    
    // Define the number of decimal places
    $decimal_places = 2;

    // Format the weight value to the specified number of decimal places
    $formatted_weight = number_format($latest_entry['weight'], $decimal_places);
    
    return $formatted_weight . ' ' . $latest_entry['unit'];
}

/**
 * Check the data structure of the weight progress for a user.
 *
 * @param int $user_id ID of the user.
 */
function check_weight_data_structure($user_id) {
    $weight_progress = get_user_meta($user_id, 'weight_progress', true);
    error_log('Weight Data Structure Check for user ' . $user_id . ':');
    error_log('Is array: ' . (is_array($weight_progress) ? 'Yes' : 'No'));
    error_log('Count: ' . (is_array($weight_progress) ? count($weight_progress) : 'Not Applicable'));
    error_log('Full data: ' . print_r($weight_progress, true));
}

/**
 * Get the most recent squat weight entry for a user.
 *
 * @param int $user_id ID of the user.
 * @return string The most recent squat weight entry or 'No data' if not found.
 */
function get_most_recent_squat_weight($user_id) {
    $squat_progress = get_user_meta($user_id, 'squat_progress', true);
    error_log('Squat Progress for user ' . $user_id . ': ' . print_r($squat_progress, true));
    
    if (!is_array($squat_progress) || empty($squat_progress)) {
        error_log('No valid squat data found for user ' . $user_id);
        return 'No data';
    }
    
    $latest_entry = reset($squat_progress);
    error_log('Latest squat entry: ' . print_r($latest_entry, true));
    
    if (!isset($latest_entry['weight']) || !isset($latest_entry['unit'])) {
        error_log('Invalid squat data structure for user ' . $user_id . '. Keys found: ' . implode(', ', array_keys($latest_entry)));
        return 'Invalid data (structure)';
    }
    
    if (!is_numeric($latest_entry['weight'])) {
        error_log('Invalid squat weight value for user ' . $user_id . '. Value: ' . $latest_entry['weight']);
        return 'Invalid data (value)';
    }
    
    // Define the number of decimal places
    $decimal_places = 2;
    // Format the squat weight value to the specified number of decimal places
    $formatted_weight = number_format($latest_entry['weight'], $decimal_places);
    
    return $formatted_weight . ' ' . $latest_entry['unit'];
}

/**
 * Check the data structure of the squat progress for a user.
 *
 * @param int $user_id ID of the user.
 */
function check_squat_data_structure($user_id) {
    $squat_progress = get_user_meta($user_id, 'squat_progress', true);
    error_log('Squat Data Structure Check for user ' . $user_id . ':');
    error_log('Is array: ' . (is_array($squat_progress) ? 'Yes' : 'No'));
    error_log('Count: ' . (is_array($squat_progress) ? count($squat_progress) : 'Not Applicable'));
    error_log('Full data: ' . print_r($squat_progress, true));
}

/**
 * Get the most recent deadlift weight entry for a user.
 *
 * @param int $user_id ID of the user.
 * @return string The most recent deadlift weight entry or 'No data' if not found.
 */
function get_most_recent_deadlift_weight($user_id) {
    $deadlift_progress = get_user_meta($user_id, 'deadlift_progress', true);
    error_log('Deadlift Progress for user ' . $user_id . ': ' . print_r($deadlift_progress, true));
    
    if (!is_array($deadlift_progress) || empty($deadlift_progress)) {
        error_log('No valid deadlift data found for user ' . $user_id);
        return 'No data';
    }
    
    $latest_entry = reset($deadlift_progress);
    error_log('Latest deadlift entry: ' . print_r($latest_entry, true));
    
    if (!isset($latest_entry['weight']) || !isset($latest_entry['unit'])) {
        error_log('Invalid deadlift data structure for user ' . $user_id . '. Keys found: ' . implode(', ', array_keys($latest_entry)));
        return 'Invalid data (structure)';
    }
    
    if (!is_numeric($latest_entry['weight'])) {
        error_log('Invalid deadlift weight value for user ' . $user_id . '. Value: ' . $latest_entry['weight']);
        return 'Invalid data (value)';
    }
    
    // Define the number of decimal places
    $decimal_places = 2;
    
    // Format the deadlift weight value to the specified number of decimal places
    $formatted_weight = number_format($latest_entry['weight'], $decimal_places);
    
    return $formatted_weight . ' ' . $latest_entry['unit'];
}

/**
 * Check the data structure of the deadlift progress for a user.
 *
 * @param int $user_id ID of the user.
 */
function check_deadlift_data_structure($user_id) {
    $deadlift_progress = get_user_meta($user_id, 'deadlift_progress', true);
    error_log('Deadlift Data Structure Check for user ' . $user_id . ':');
    error_log('Is array: ' . (is_array($deadlift_progress) ? 'Yes' : 'No'));
    error_log('Count: ' . (is_array($deadlift_progress) ? count($deadlift_progress) : 'Not Applicable'));
    error_log('Full data: ' . print_r($deadlift_progress, true));
}


/**
 * Handle profile picture upload.
 *
 * @return array Result of the upload operation.
 */
function athlete_dashboard_handle_profile_picture_upload() {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploadedfile = $_FILES['profile_picture'];
    $upload_overrides = array('test_form' => false);

    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $user_id = get_current_user_id();
        $attachment_id = wp_insert_attachment(
            array(
                'guid'           => $movefile['url'],
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ),
            $movefile['file']
        );

        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            update_user_meta($user_id, 'wp_user_avatar', $attachment_id);

            return array('success' => true, 'data' => array('url' => $movefile['url']));
        } else {
            return array('success' => false, 'data' => array('message' => $attachment_id->get_error_message()));
        }
    } else {
        return array('success' => false, 'data' => array('message' => $movefile['error']));
    }
}

/**
 * Get exercise tests for the user profile.
 *
 * @return array An array of exercise tests with their details.
 */
function get_exercise_tests() {
    return array(
        '5k_run' => array('label' => '5k Run', 'unit' => 'minutes', 'decimal_places' => 2),
        '20k_cycling' => array('label' => '20k Cycling', 'unit' => 'minutes', 'decimal_places' => 2),
        '10k_rucking' => array('label' => '10k Rucking', 'unit' => 'minutes', 'decimal_places' => 2),
        '400m_swim' => array('label' => '400m Swim', 'unit' => 'seconds', 'decimal_places' => 1),
        'slrdl' => array('label' => 'Single-Leg Romanian Deadlift', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => true),
        'pistol_squat' => array('label' => 'Single-Leg Squat', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => true),
        'pushups' => array('label' => 'Push-Ups', 'unit' => 'reps', 'decimal_places' => 0),
        'pullups' => array('label' => 'Pull-Ups', 'unit' => 'reps', 'decimal_places' => 0),
        'vertical_jump' => array('label' => 'Vertical Jump', 'unit' => 'inches', 'decimal_places' => 1),
        'sit_reach' => array('label' => 'Sit-and-Reach', 'unit' => 'inches', 'decimal_places' => 1),
        'balance_test' => array('label' => 'Single-Leg Balance', 'unit' => 'seconds', 'decimal_places' => 1, 'bilateral' => true),
        'farmers_walk' => array('label' => 'Loaded Carry', 'unit' => 'meters', 'decimal_places' => 1),
        'burpee_test' => array('label' => 'Burpee Test', 'unit' => 'reps', 'decimal_places' => 0),
        'deadhang' => array('label' => 'Deadhang', 'unit' => 'seconds', 'decimal_places' => 1),
        'plank' => array('label' => 'Plank', 'unit' => 'seconds', 'decimal_places' => 1),
        'situps' => array('label' => 'Sit-Ups', 'unit' => 'reps', 'decimal_places' => 0),
        '1k_walk' => array('label' => '1K Walk', 'unit' => 'minutes', 'decimal_places' => 2),
        'body_weight_squats' => array('label' => 'Body Weight Squats', 'unit' => 'reps', 'decimal_places' => 0)
    );
}

/**
 * Check if the current user can manage workout logs.
 *
 * @return bool True if the user can manage workout logs, false otherwise.
 */
function athlete_dashboard_can_manage_workout_logs() {
    return current_user_can('publish_log_workouts') && current_user_can('edit_log_workouts');
}

/**
 * Add workout log management section to user profile.
 *
 * @param WP_User $user User object.
 */
function athlete_dashboard_add_workout_log_section($user) {
    if (!athlete_dashboard_can_manage_workout_logs()) {
        return;
    }
    ?>
    <h3><?php esc_html_e("Workout Logs", "athlete-dashboard"); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="workout_log_management"><?php esc_html_e("Manage Workout Logs", "athlete-dashboard"); ?></label></th>
            <td>
                <p><?php esc_html_e("You can manage your workout logs from the dashboard.", "athlete-dashboard"); ?></p>
                <a href="<?php echo esc_url(home_url('/athlete-dashboard/')); ?>" class="button"><?php esc_html_e("Go to Dashboard", "athlete-dashboard"); ?></a>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'athlete_dashboard_add_workout_log_section');
add_action('edit_user_profile', 'athlete_dashboard_add_workout_log_section');

/**
 * Check if the current user can manage meal logs.
 *
 * @return bool True if the user can manage meal logs, false otherwise.
 */
function athlete_dashboard_can_manage_meal_logs() {
    return current_user_can('publish_meal_logs') && current_user_can('edit_meal_logs');
}

/**
 * Add meal log management section to user profile.
 *
 * @param WP_User $user User object.
 */
function athlete_dashboard_add_meal_log_section($user) {
    if (!athlete_dashboard_can_manage_meal_logs()) {
        return;
    }
    ?>
    <h3><?php esc_html_e("Meal Logs", "athlete-dashboard"); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="meal_log_management"><?php esc_html_e("Manage Meal Logs", "athlete-dashboard"); ?></label></th>
            <td>
                <p><?php esc_html_e("You can manage your meal logs from the dashboard.", "athlete-dashboard"); ?></p>
                <a href="<?php echo esc_url(home_url('/athlete-dashboard/')); ?>" class="button"><?php esc_html_e("Go to Dashboard", "athlete-dashboard"); ?></a>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'athlete_dashboard_add_meal_log_section');
add_action('edit_user_profile', 'athlete_dashboard_add_meal_log_section');

function athlete_dashboard_add_messaging_to_profile($user) {
    if (!current_user_can('edit_users') && !in_array('author', wp_get_current_user()->roles)) {
        return;
    }
    ?>
    <h2><?php esc_html_e('Athlete Messages', 'athlete-dashboard'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="athlete-messages"><?php esc_html_e('Recent Messages', 'athlete-dashboard'); ?></label></th>
            <td>
                <div id="athlete-messages">
                    <?php echo do_shortcode('[athlete_recent_messages user_id="' . $user->ID . '"]'); ?>
                </div>
                <textarea id="new-message-content" rows="4" cols="50"></textarea>
                <br>
                <button type="button" id="send-athlete-message" class="button" data-user-id="<?php echo esc_attr($user->ID); ?>"><?php esc_html_e('Send Message', 'athlete-dashboard'); ?></button>
            </td>
        </tr>
    </table>
    <?php
    // Ensure the messaging script is enqueued
    wp_enqueue_script('messaging-scripts');
    
    // Localize the script with necessary data
    wp_localize_script('messaging-scripts', 'athleteDashboardMessaging', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'admin_nonce' => wp_create_nonce('athlete_dashboard_admin_nonce'),
        'is_admin' => true
    ));
}
add_action('show_user_profile', 'athlete_dashboard_add_messaging_to_profile');
add_action('edit_user_profile', 'athlete_dashboard_add_messaging_to_profile');
