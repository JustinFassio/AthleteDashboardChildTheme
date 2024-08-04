<?php
/**
 * AJAX Handlers for Athlete Dashboard
 *
 * This file contains all AJAX handler functions for the Athlete Dashboard.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Verify AJAX nonce and user authentication
 *
 * @return void
 */
function athlete_dashboard_verify_ajax_request() {
    if (!check_ajax_referer('athlete_dashboard_nonce', 'nonce', false) || !is_user_logged_in()) {
        wp_send_json_error(['message' => __('Security check failed or user not logged in.', 'athlete-dashboard')]);
    }
}

/**
 * Generic AJAX handler for calling dashboard functions
 *
 * @param string $function_name The name of the function to call
 * @param array $arguments Additional arguments to pass to the function
 * @return void
 */
function athlete_dashboard_generic_ajax_handler($function_name, $arguments = []) {
    athlete_dashboard_verify_ajax_request();
    
    if (function_exists($function_name)) {
        $user_id = get_current_user_id();
        $result = call_user_func_array($function_name, array_merge([$user_id], $arguments));
        wp_send_json_success($result);
    } else {
        error_log("Function $function_name not found in athlete_dashboard_generic_ajax_handler");
        wp_send_json_error(['message' => __('Required function not found.', 'athlete-dashboard')]);
    }
}

/**
 * AJAX Handler for full workout lightbox
 */
function athlete_dashboard_get_full_workout() {
    athlete_dashboard_verify_ajax_nonce();
    
    $workout_id = isset($_POST['workout_id']) ? intval($_POST['workout_id']) : 0;
    
    if (!$workout_id) {
        wp_send_json_error(['message' => 'Invalid workout ID']);
    }

    $workout = get_post($workout_id);
    
    if (!$workout) {
        wp_send_json_error(['message' => 'Workout not found']);
    }

    $workout_content = athlete_dashboard_generate_full_workout_content($workout);

    wp_send_json_success($workout_content);
}
add_action('wp_ajax_get_full_workout', 'athlete_dashboard_get_full_workout');


//Generate full workout content
function athlete_dashboard_generate_full_workout_content($workout) {
    $content = '<div class="full-workout" style="color: #333; font-family: Arial, sans-serif;">';
    
    // Workout title and date
    $content .= '<h2 style="color: #1e88e5;">' . esc_html($workout->post_title) . '</h2>';
    $content .= '<p class="workout-date" style="font-style: italic;">' . esc_html(get_the_date('', $workout)) . '</p>';

    // Convert post content to HTML
    $workout_content = wpautop(wp_kses_post($workout->post_content));
    
    // Apply content filters (this will handle shortcodes and other WordPress content processing)
    $workout_content = apply_filters('the_content', $workout_content);

    // Add the processed content to our output
    $content .= '<div class="workout-content" style="margin-bottom: 20px;">' . $workout_content . '</div>';

    // Detailed exercise list (if available)
    $exercises = get_post_meta($workout->ID, 'exercises', true);
    if ($exercises && is_array($exercises)) {
        $content .= '<h3 style="color: #43a047;">Exercises</h3>';
        $content .= '<ul class="exercise-list" style="list-style-type: none; padding-left: 0;">';
        foreach ($exercises as $exercise) {
            $content .= '<li class="exercise-item" style="margin-bottom: 10px; padding: 5px; background-color: #f5f5f5;">';
            $content .= '<span class="exercise-name" style="font-weight: bold;">' . esc_html($exercise['name']) . '</span>: ';
            $content .= '<span class="exercise-details">' . esc_html($exercise['sets']) . ' sets, ' . esc_html($exercise['reps']) . ' reps';
            if (!empty($exercise['weight'])) {
                $content .= ', ' . esc_html($exercise['weight']) . ' ' . esc_html($exercise['weight_unit']);
            }
            $content .= '</span>';
            
            // Add link to exercise demonstration if available
            if (!empty($exercise['demo_link'])) {
                $content .= ' <a href="' . esc_url($exercise['demo_link']) . '" target="_blank" style="color: #1e88e5; text-decoration: none;">View Demo</a>';
            }
            
            $content .= '</li>';
        }
        $content .= '</ul>';
    }

    // Notes or instructions
    $notes = get_post_meta($workout->ID, 'workout_notes', true);
    if ($notes) {
        $content .= '<div class="workout-notes">';
        $content .= '<h3 style="color: #43a047;">Notes:</h3>';
        $content .= wpautop(wp_kses_post($notes));
        $content .= '</div>';
    }

    // Add "Close Workout" button
    $content .= '<button id="close-workout" style="background-color: #e53935; color: white; border: none; padding: 10px 20px; cursor: pointer; margin-top: 20px;">Close Workout</button>';

    $content .= '</div>';

    return $content;
}

/**
 * AJAX handler for getting most recent weight
 */
function athlete_dashboard_ajax_get_most_recent_weight() {
    athlete_dashboard_generic_ajax_handler('athlete_dashboard_get_most_recent_weight');
}
add_action('wp_ajax_athlete_dashboard_get_most_recent_weight', 'athlete_dashboard_ajax_get_most_recent_weight');

/**
 * AJAX handler for getting user weight progress
 */
function athlete_dashboard_ajax_get_user_weight_progress() {
    athlete_dashboard_verify_ajax_request();
    
    try {
        $progress = athlete_dashboard_get_progress_chart_data('weight');
        wp_send_json_success($progress);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Error retrieving weight progress data']);
    }
}
add_action('wp_ajax_athlete_dashboard_get_user_progress', 'athlete_dashboard_ajax_get_user_weight_progress');

/**
 * AJAX handler for weight progress submission
 */
function athlete_dashboard_ajax_handle_weight_progress_submission() {
    athlete_dashboard_verify_ajax_request();
    
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $unit = isset($_POST['weight_unit']) ? sanitize_text_field($_POST['weight_unit']) : 'kg';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('mysql');
    
    $user_id = get_current_user_id();
    $result = athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, 'weight');
    
    if ($result) {
        $updated_progress = athlete_dashboard_get_progress_chart_data('weight');
        wp_send_json_success([
            'message' => __('Weight progress updated successfully', 'athlete-dashboard'),
            'data' => $updated_progress
        ]);
    } else {
        wp_send_json_error(['message' => __('Failed to update weight progress', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_handle_progress_submission', 'athlete_dashboard_ajax_handle_weight_progress_submission');

/**
 * AJAX handler for getting user squat progress
 */
function athlete_dashboard_ajax_get_user_squat_progress() {
    athlete_dashboard_verify_ajax_request();
    
    $progress = athlete_dashboard_get_progress_chart_data('squat');
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_squat_progress', 'athlete_dashboard_ajax_get_user_squat_progress');

/**
 * AJAX handler for squat progress submission
 */
function athlete_dashboard_ajax_handle_squat_progress_submission() {
    athlete_dashboard_verify_ajax_request();
    
    $squat_weight = isset($_POST['squat_weight']) ? floatval($_POST['squat_weight']) : 0;
    $unit = isset($_POST['squat_weight_unit']) ? sanitize_text_field($_POST['squat_weight_unit']) : 'kg';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('mysql');
    
    athlete_dashboard_generic_ajax_handler('athlete_dashboard_handle_squat_progress_submission', [$squat_weight, $unit, $date]);
}
add_action('wp_ajax_athlete_dashboard_handle_squat_progress_submission', 'athlete_dashboard_ajax_handle_squat_progress_submission');

/**
 * AJAX handler for getting user bench press progress
 */
function athlete_dashboard_ajax_get_user_bench_press_progress() {
    athlete_dashboard_verify_ajax_request();
    
    $progress = athlete_dashboard_get_progress_chart_data('bench_press');
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_bench_press_progress', 'athlete_dashboard_ajax_get_user_bench_press_progress');

/**
 * AJAX handler for bench press progress submission
 */
function athlete_dashboard_ajax_handle_bench_press_progress_submission() {
    athlete_dashboard_verify_ajax_request();
    
    $bench_press_weight = isset($_POST['bench_press_weight']) ? floatval($_POST['bench_press_weight']) : 0;
    $unit = isset($_POST['bench_press_weight_unit']) ? sanitize_text_field($_POST['bench_press_weight_unit']) : 'kg';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('mysql');
    
    athlete_dashboard_generic_ajax_handler('athlete_dashboard_handle_bench_press_progress_submission', [$bench_press_weight, $unit, $date]);
}
add_action('wp_ajax_athlete_dashboard_handle_bench_press_progress_submission', 'athlete_dashboard_ajax_handle_bench_press_progress_submission');

/**
 * AJAX handler for getting user deadlift progress
 */
function athlete_dashboard_ajax_get_user_deadlift_progress() {
    athlete_dashboard_verify_ajax_request();
    
    $progress = athlete_dashboard_get_progress_chart_data('deadlift');
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_deadlift_progress', 'athlete_dashboard_ajax_get_user_deadlift_progress');

/**
 * AJAX handler for deadlift progress submission
 */
function athlete_dashboard_ajax_handle_deadlift_progress_submission() {
    athlete_dashboard_verify_ajax_request();
    
    $deadlift_weight = isset($_POST['deadlift_weight']) ? floatval($_POST['deadlift_weight']) : 0;
    $unit = isset($_POST['deadlift_weight_unit']) ? sanitize_text_field($_POST['deadlift_weight_unit']) : 'kg';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('mysql');
    
    $user_id = get_current_user_id();
    $result = athlete_dashboard_handle_deadlift_progress_submission($user_id, $deadlift_weight, $unit, $date);
    
    if ($result) {
        $updated_progress = athlete_dashboard_get_progress_chart_data('deadlift');
        wp_send_json_success([
            'message' => __('Deadlift progress updated successfully', 'athlete-dashboard'),
            'data' => $updated_progress
        ]);
    } else {
        wp_send_json_error(['message' => __('Failed to update deadlift progress', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_handle_deadlift_progress_submission', 'athlete_dashboard_ajax_handle_deadlift_progress_submission');

add_action('wp_ajax_save_body_composition', 'save_body_composition_data');
function save_body_composition_data() {
    check_ajax_referer('athlete_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    $data = array(
        'weight' => floatval($_POST['weight']),
        'body_fat' => floatval($_POST['body_fat']),
        'lean_mass' => floatval($_POST['lean_mass']),
        'bmi' => floatval($_POST['bmi']),
        'date' => sanitize_text_field($_POST['date'])
    );

    // Save to user meta or custom table
    $saved = update_user_meta($user_id, 'body_composition_' . $data['date'], $data);

    if ($saved) {
        wp_send_json_success('Data saved successfully');
    } else {
        wp_send_json_error('Error saving data');
    }
}

add_action('wp_ajax_get_body_composition_data', 'get_body_composition_data');
function get_body_composition_data() {
    check_ajax_referer('athlete_dashboard_nonce', 'nonce');

    $user_id = get_current_user_id();
    $data = array();

    // Fetch data from user meta or custom table
    $user_meta = get_user_meta($user_id);
    foreach ($user_meta as $key => $value) {
        if (strpos($key, 'body_composition_') === 0) {
            $data[] = maybe_unserialize($value[0]);
        }
    }

    // Sort data by date
    usort($data, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    wp_send_json_success($data);
}

/**
 * AJAX handler for profile picture upload
 */
function athlete_dashboard_ajax_handle_profile_picture_upload() {
    athlete_dashboard_verify_ajax_request();
    
    if (function_exists('athlete_dashboard_handle_profile_picture_upload')) {
        $result = athlete_dashboard_handle_profile_picture_upload();
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['data']);
        }
    } else {
        wp_send_json_error(['message' => __('Profile picture upload function not found.', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_update_profile_picture', 'athlete_dashboard_ajax_handle_profile_picture_upload');

/**
 * AJAX handler for general profile update
 */
function athlete_dashboard_ajax_handle_profile_update() {
    athlete_dashboard_verify_ajax_request();
    
    $user_id = get_current_user_id();
    $user_data = array(
        'ID' => $user_id,
        'display_name' => isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '',
        'user_email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'description' => isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : ''
    );

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['message' => __('Profile updated successfully', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_update_profile', 'athlete_dashboard_ajax_handle_profile_update');

/**
 * AJAX handler for workout log submission
 */
function athlete_dashboard_ajax_handle_workout_log_submission() {
    athlete_dashboard_verify_ajax_request();
    
    if (!current_user_can('publish_log_workouts')) {
        wp_send_json_error(['message' => __('You do not have permission to submit workout logs.', 'athlete-dashboard')]);
    }

    $user_id = get_current_user_id();
    $workout_data = array(
        'post_title'   => sprintf(__('Workout on %s', 'athlete-dashboard'), current_time('Y-m-d')),
        'post_content' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
        'post_status'  => 'publish',
        'post_author'  => $user_id,
        'post_type'    => 'log_workout',
    );

    $post_id = wp_insert_post($workout_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    } else {
        update_post_meta($post_id, '_workout_date', sanitize_text_field($_POST['workout_date']));
        update_post_meta($post_id, '_workout_duration', intval($_POST['workout_duration']));
        update_post_meta($post_id, '_workout_type', sanitize_text_field($_POST['workout_type']));
        update_post_meta($post_id, '_workout_intensity', intval($_POST['workout_intensity']));

        wp_send_json_success(['message' => __('Workout log submitted successfully', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_submit_workout_log', 'athlete_dashboard_ajax_handle_workout_log_submission');

/**
 * AJAX handler for getting recent workouts
 */
function athlete_dashboard_ajax_get_recent_workouts() {
    athlete_dashboard_verify_ajax_request();

    $user_id = get_current_user_id();
    $recent_workouts = get_posts(array(
        'post_type'      => 'log_workout',
        'author'         => $user_id,
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    $workout_data = array();
    foreach ($recent_workouts as $workout) {
        $workout_data[] = array(
            'date'      => get_post_meta($workout->ID, '_workout_date', true),
            'duration'  => get_post_meta($workout->ID, '_workout_duration', true),
            'type'      => get_post_meta($workout->ID, '_workout_type', true),
            'intensity' => get_post_meta($workout->ID, '_workout_intensity', true),
            'notes'     => $workout->post_content,
        );
    }

    wp_send_json_success(['workouts' => $workout_data]);
}
add_action('wp_ajax_athlete_dashboard_get_recent_workouts', 'athlete_dashboard_ajax_get_recent_workouts');

/**
 * AJAX handler for submitting meal logs
 */
function athlete_dashboard_ajax_handle_meal_log_submission() {
    athlete_dashboard_verify_ajax_request();
    
    if (!current_user_can('publish_meal_logs')) {
        wp_send_json_error(['message' => __('You do not have permission to submit meal logs.', 'athlete-dashboard')]);
    }
    
    $user_id = get_current_user_id();
    $meal_date = sanitize_text_field($_POST['meal_date']);
    $meal_time = sanitize_text_field($_POST['meal_time']);
    $meal_datetime = $meal_date . ' ' . $meal_time;
    
    $meal_data = array(
        'post_title'   => sprintf(__('Meal on %s', 'athlete-dashboard'), $meal_datetime),
        'post_content' => isset($_POST['meal_description']) ? sanitize_textarea_field($_POST['meal_description']) : '',
        'post_status'  => 'publish',
        'post_author'  => $user_id,
        'post_type'    => 'meal_log',
    );
    
    $post_id = wp_insert_post($meal_data);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    } else {
        $meta_fields = array(
            'meal_date', 'meal_time', 'meal_type', 'meal_name', 'estimated_calories',
            'protein_type', 'protein_quantity', 'protein_unit',
            'fat_type', 'fat_quantity', 'fat_unit',
            'carb_starch_type', 'carb_starch_quantity', 'carb_starch_unit',
            'carb_fruit_type', 'carb_fruit_quantity', 'carb_fruit_unit',
            'carb_vegetable_type', 'carb_vegetable_quantity', 'carb_vegetable_unit'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if ($field === 'estimated_calories') {
                    $value = intval($value);
                } elseif (in_array($field, ['protein_quantity', 'fat_quantity', 'carb_starch_quantity', 'carb_fruit_quantity', 'carb_vegetable_quantity'])) {
                    $value = floatval($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        wp_send_json_success([
            'message' => __('Meal log submitted successfully', 'athlete-dashboard'),
            'post_id' => $post_id
        ]);
    }
}
add_action('wp_ajax_athlete_dashboard_submit_meal_log', 'athlete_dashboard_ajax_handle_meal_log_submission');

/**
 * AJAX handler for getting recent meal logs
 */
function athlete_dashboard_ajax_get_recent_meals() {
    athlete_dashboard_verify_ajax_request();
    
    $user_id = get_current_user_id();
    $recent_meals = get_posts(array(
        'post_type'      => 'meal_log',
        'author'         => $user_id,
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    
    $meal_data = array();
    foreach ($recent_meals as $meal) {
        $meal_date = get_post_meta($meal->ID, '_meal_date', true);
        $meal_time = get_post_meta($meal->ID, '_meal_time', true);
        
        $meal_info = array(
            'id'          => $meal->ID,
            'date'        => $meal_date,
            'time'        => $meal_time,
            'datetime'    => $meal_date . ' ' . $meal_time,
            'type'        => get_post_meta($meal->ID, '_meal_type', true),
            'name'        => get_post_meta($meal->ID, '_meal_name', true),
            'calories'    => get_post_meta($meal->ID, '_estimated_calories', true),
            'description' => $meal->post_content,
        );
        
        $nutritional_fields = array(
            'protein', 'fat', 'carb_starch', 'carb_fruit', 'carb_vegetable'
        );
        
        foreach ($nutritional_fields as $nutrient) {
            $meal_info[$nutrient] = array(
                'type'     => get_post_meta($meal->ID, "_{$nutrient}_type", true),
                'quantity' => get_post_meta($meal->ID, "_{$nutrient}_quantity", true),
                'unit'     => get_post_meta($meal->ID, "_{$nutrient}_unit", true)
            );
        }
        
        $meal_data[] = $meal_info;
    }
    
    wp_send_json_success(['meals' => $meal_data]);
}
add_action('wp_ajax_athlete_dashboard_get_recent_meals', 'athlete_dashboard_ajax_get_recent_meals');

// AJAX handlers for messaging
function athlete_dashboard_ajax_get_conversations() {
    check_ajax_referer('athlete_dashboard_messaging_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
    }

    $user_id = get_current_user_id();
    $conversations = athlete_dashboard_get_conversations($user_id);
    
    wp_send_json_success($conversations);
}
add_action('wp_ajax_get_conversations', 'athlete_dashboard_ajax_get_conversations');

function athlete_dashboard_ajax_get_messages() {
    check_ajax_referer('athlete_dashboard_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
    
    if (!$conversation_id) {
        wp_send_json_error('Invalid conversation ID');
    }

    $messages = athlete_dashboard_get_messages($conversation_id);
    
    wp_send_json_success($messages);
}
add_action('wp_ajax_get_messages', 'athlete_dashboard_ajax_get_messages');

function athlete_dashboard_ajax_send_message() {
    check_ajax_referer('athlete_dashboard_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    
    if (!$conversation_id || empty($message)) {
        wp_send_json_error('Invalid data');
    }

    $sender_id = get_current_user_id();
    $result = athlete_dashboard_send_message($conversation_id, $sender_id, $message);
    
    if ($result) {
        wp_send_json_success('Message sent successfully');
    } else {
        wp_send_json_error('Failed to send message');
    }
}
add_action('wp_ajax_send_message', 'athlete_dashboard_ajax_send_message');

/**
 * AJAX handler for sending a message from admin area
 */
function athlete_dashboard_send_admin_message() {
    check_ajax_referer('athlete_dashboard_admin_nonce', 'nonce');
    
    if (!current_user_can('edit_users') && !in_array('author', wp_get_current_user()->roles)) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $message_content = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    if (!$user_id || empty($message_content)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $sender_id = get_current_user_id();

    error_log("Attempting to send message from user $sender_id to user $user_id");

    $result = athlete_dashboard_send_message_to_user($user_id, $sender_id, $message_content);

    if ($result === true) {
        $html = do_shortcode('[athlete_recent_messages user_id="' . $user_id . '"]');
        wp_send_json_success(['message' => 'Message sent successfully', 'html' => $html]);
    } else {
        error_log("Failed to send message. Error: " . print_r($result, true));
        wp_send_json_error(['message' => 'Failed to send message: ' . $result]);
    }
}
add_action('wp_ajax_send_athlete_message', 'athlete_dashboard_send_admin_message');