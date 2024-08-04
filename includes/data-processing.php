<?php
/**
 * Enhanced Athlete Body Composition Tracking System - Data Processing
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Log body composition related data and messages
 *
 * @param string $message The message to log
 * @param mixed $data Optional data to log
 */
function athlete_dashboard_log_body_composition_data($message, $data = null) {
    $log_file = WP_CONTENT_DIR . '/body_composition_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $log_message .= ': ' . print_r($data, true);
    }
    $log_message .= "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Define the schema for body composition progress entries
 *
 * @return array The schema definition
 */
function athlete_dashboard_get_body_composition_progress_schema() {
    return array(
        'date' => array('type' => 'date', 'required' => true),
        'weight' => array('type' => 'float', 'required' => true, 'minimum' => 20, 'maximum' => 500),
        'body_fat_percentage' => array('type' => 'float', 'required' => false, 'minimum' => 1, 'maximum' => 60),
        'muscle_mass' => array('type' => 'float', 'required' => false, 'minimum' => 10, 'maximum' => 300),
        'bmi' => array('type' => 'float', 'required' => false, 'minimum' => 10, 'maximum' => 50),
        'notes' => array('type' => 'string', 'required' => false, 'maximum_length' => 500)
    );
}

/**
 * Sanitize and validate a body composition progress entry
 *
 * @param array $entry The progress entry to validate
 * @return array Sanitized entry and any errors
 */
function athlete_dashboard_sanitize_and_validate_body_composition_entry($entry) {
    $schema = athlete_dashboard_get_body_composition_progress_schema();
    $sanitized_entry = array();
    $errors = array();

    foreach ($schema as $field => $rules) {
        if (!isset($entry[$field]) && $rules['required']) {
            $errors[] = sprintf(__("Field '%s' is required.", 'athlete-dashboard'), $field);
            continue;
        }

        $value = isset($entry[$field]) ? $entry[$field] : null;

        switch ($rules['type']) {
            case 'date':
                $sanitized_entry[$field] = sanitize_text_field($value);
                if (!wp_date('Y-m-d H:i:s', strtotime($sanitized_entry[$field]))) {
                    $errors[] = sprintf(__("Invalid date format for '%s'.", 'athlete-dashboard'), $field);
                }
                break;
            case 'float':
                $sanitized_entry[$field] = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                if ($sanitized_entry[$field] === false) {
                    $errors[] = sprintf(__("Invalid float value for '%s'.", 'athlete-dashboard'), $field);
                } elseif (isset($rules['minimum']) && $sanitized_entry[$field] < $rules['minimum']) {
                    $errors[] = sprintf(__("'%s' must be at least %s.", 'athlete-dashboard'), $field, $rules['minimum']);
                } elseif (isset($rules['maximum']) && $sanitized_entry[$field] > $rules['maximum']) {
                    $errors[] = sprintf(__("'%s' must not exceed %s.", 'athlete-dashboard'), $field, $rules['maximum']);
                }
                break;
            case 'string':
                $sanitized_entry[$field] = sanitize_textarea_field($value);
                if (isset($rules['maximum_length']) && strlen($sanitized_entry[$field]) > $rules['maximum_length']) {
                    $errors[] = sprintf(__("'%s' must not exceed %d characters.", 'athlete-dashboard'), $field, $rules['maximum_length']);
                }
                break;
        }
    }

    return array(
        'sanitized_entry' => $sanitized_entry,
        'errors' => $errors
    );
}

/**
 * Store a body composition progress entry for a user
 *
 * @param integer $user_id The ID of the user
 * @param array $entry The progress entry to store
 * @return array Result of the operation
 */
function athlete_dashboard_store_user_body_composition_progress($user_id, $entry) {
    athlete_dashboard_log_body_composition_data("Attempting to store body composition data for user", $user_id);
    athlete_dashboard_log_body_composition_data("Input data", $entry);

    $result = athlete_dashboard_sanitize_and_validate_body_composition_entry($entry);
    if (!empty($result['errors'])) {
        athlete_dashboard_log_body_composition_data("Validation errors", $result['errors']);
        return array('success' => false, 'errors' => $result['errors']);
    }

    $progress = get_user_meta($user_id, 'body_composition_progress', true);
    if (!is_array($progress)) {
        $progress = array();
    }

    $entry_date = $result['sanitized_entry']['date'];
    $existing_entry_index = array_search($entry_date, array_column($progress, 'date'));

    if ($existing_entry_index !== false) {
        $progress[$existing_entry_index] = $result['sanitized_entry'];
    } else {
        $progress[] = $result['sanitized_entry'];
    }

    usort($progress, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    update_user_meta($user_id, 'body_composition_progress', $progress);

    athlete_dashboard_log_body_composition_data("Body composition data stored successfully", $progress);
    return array('success' => true, 'message' => __('Progress updated successfully', 'athlete-dashboard'));
}

/**
 * Retrieve body composition progress entries for a user
 *
 * @param integer $user_id The ID of the user
 * @param string $start_date Optional start date for filtering
 * @param string $end_date Optional end date for filtering
 * @param string $metric Optional specific metric to retrieve
 * @param string $sort_order Optional sort order ('date_ascending' or 'date_descending')
 * @return array The progress entries
 */
function athlete_dashboard_get_user_body_composition_progress($user_id, $start_date = null, $end_date = null, $metric = 'all', $sort_order = 'date_ascending') {
    athlete_dashboard_log_body_composition_data("Retrieving body composition data for user", $user_id);
    athlete_dashboard_log_body_composition_data("Query parameters", array(
        'start_date' => $start_date,
        'end_date' => $end_date,
        'metric' => $metric,
        'sort_order' => $sort_order
    ));

    $progress = get_user_meta($user_id, 'body_composition_progress', true);
    if (!is_array($progress)) {
        return array();
    }

    if ($start_date || $end_date) {
        $progress = array_filter($progress, function($entry) use ($start_date, $end_date) {
            $entry_date = strtotime($entry['date']);
            return (!$start_date || $entry_date >= strtotime($start_date)) &&
                   (!$end_date || $entry_date <= strtotime($end_date));
        });
    }

    if ($metric !== 'all') {
        $progress = array_map(function($entry) use ($metric) {
            return array(
                'date' => $entry['date'],
                $metric => isset($entry[$metric]) ? $entry[$metric] : null
            );
        }, $progress);
    }

    usort($progress, function($a, $b) use ($sort_order) {
        $date_comparison = strtotime($a['date']) - strtotime($b['date']);
        return $sort_order === 'date_ascending' ? $date_comparison : -$date_comparison;
    });

    athlete_dashboard_log_body_composition_data("Retrieved body composition data", $progress);
    return $progress;
}

/**
 * Delete a specific body composition progress entry for a user
 *
 * @param integer $user_id The ID of the user
 * @param string $entry_date The date of the entry to delete
 * @return array Result of the operation
 */
function athlete_dashboard_delete_user_body_composition_progress_entry($user_id, $entry_date) {
    $progress = get_user_meta($user_id, 'body_composition_progress', true);
    
    if (!is_array($progress)) {
        return array('success' => false, 'message' => __('No progress data found', 'athlete-dashboard'));
    }

    $updated_progress = array_filter($progress, function($entry) use ($entry_date) {
        return $entry['date'] !== $entry_date;
    });

    if (count($updated_progress) === count($progress)) {
        return array('success' => false, 'message' => __('Progress entry not found', 'athlete-dashboard'));
    }

    $updated = update_user_meta($user_id, 'body_composition_progress', $updated_progress);

    if ($updated) {
        return array('success' => true, 'message' => __('Progress entry deleted successfully', 'athlete-dashboard'));
    } else {
        return array('success' => false, 'message' => __('Failed to delete progress entry', 'athlete-dashboard'));
    }
}

/**
 * Migrate old weight progress data to the new body composition format
 *
 * @return string Migration result message
 */
function athlete_dashboard_migrate_body_composition_data() {
    $users = get_users(array('fields' => 'ID'));
    $migration_log = array();

    foreach ($users as $user_id) {
        $old_progress = get_user_meta($user_id, 'weight_progress', true);
        $new_progress = array();

        if (is_array($old_progress)) {
            foreach ($old_progress as $entry) {
                $new_entry = array(
                    'date' => $entry['date'],
                    'weight' => floatval($entry['weight']),
                    'body_fat_percentage' => null,
                    'muscle_mass' => null,
                    'bmi' => null
                );
                $new_progress[] = $new_entry;
            }

            update_user_meta($user_id, 'body_composition_progress', $new_progress);
            delete_user_meta($user_id, 'weight_progress');
            $migration_log[] = sprintf(__("User ID %d: Migrated %d entries.", 'athlete-dashboard'), $user_id, count($new_progress));
        } else {
            $migration_log[] = sprintf(__("User ID %d: No weight progress data found.", 'athlete-dashboard'), $user_id);
        }
    }

    $log_file_path = WP_CONTENT_DIR . '/body_composition_migration_log.txt';
    file_put_contents($log_file_path, implode("\n", $migration_log));

    return sprintf(__("Migration completed. Log file created at %s", 'athlete-dashboard'), $log_file_path);
}

/**
 * Rollback the body composition data migration
 *
 * @return string Rollback result message
 */
function athlete_dashboard_rollback_body_composition_migration() {
    $users = get_users(array('fields' => 'ID'));
    $rollback_log = array();

    foreach ($users as $user_id) {
        $new_progress = get_user_meta($user_id, 'body_composition_progress', true);
        $old_progress = array();

        if (is_array($new_progress)) {
            foreach ($new_progress as $entry) {
                $old_entry = array(
                    'date' => $entry['date'],
                    'weight' => $entry['weight'],
                    'unit' => 'kg'
                );
                $old_progress[] = $old_entry;
            }

            update_user_meta($user_id, 'weight_progress', $old_progress);
            delete_user_meta($user_id, 'body_composition_progress');
            $rollback_log[] = sprintf(__("User ID %d: Rolled back %d entries.", 'athlete-dashboard'), $user_id, count($old_progress));
        } else {
            $rollback_log[] = sprintf(__("User ID %d: No body composition progress data found.", 'athlete-dashboard'), $user_id);
        }
    }

    $log_file_path = WP_CONTENT_DIR . '/body_composition_rollback_log.txt';
    file_put_contents($log_file_path, implode("\n", $rollback_log));

    return sprintf(__("Rollback completed. Log file created at %s", 'athlete-dashboard'), $log_file_path);
}

/**
 * Store a workout log entry for a user
 *
 * @param integer $user_id The ID of the user
 * @param array $entry The workout log entry to store
 * @return array Result of the operation
 */
function athlete_dashboard_store_workout_log($user_id, $entry) {
    athlete_dashboard_log_data("Attempting to store workout log for user", $user_id);
    athlete_dashboard_log_data("Input data", $entry);

    $result = athlete_dashboard_sanitize_and_validate_workout_log($entry);
    if (!empty($result['errors'])) {
        athlete_dashboard_log_data("Validation errors", $result['errors']);
        return array('success' => false, 'errors' => $result['errors']);
    }

    $post_id = wp_insert_post(array(
        'post_title'    => sprintf(__('Workout on %s', 'athlete-dashboard'), $result['sanitized_entry']['workout_date']),
        'post_content'  => $result['sanitized_entry']['notes'],
        'post_status'   => 'publish',
        'post_author'   => $user_id,
        'post_type'     => 'log_workout',
    ));

    if (is_wp_error($post_id)) {
        return array('success' => false, 'errors' => array($post_id->get_error_message()));
    }

    foreach ($result['sanitized_entry'] as $key => $value) {
        update_post_meta($post_id, '_' . $key, $value);
    }

    athlete_dashboard_log_data("Workout log stored successfully", $post_id);
    return array('success' => true, 'message' => __('Workout log added successfully', 'athlete-dashboard'));
}

/**
 * Sanitize and validate a workout log entry
 *
 * @param array $entry The workout log entry to validate
 * @return array Sanitized entry and any errors
 */
function athlete_dashboard_sanitize_and_validate_workout_log($entry) {
    $sanitized_entry = array();
    $errors = array();

    $fields = array(
        'workout_date' => array('type' => 'date', 'required' => true),
        'workout_duration' => array('type' => 'int', 'required' => true, 'min' => 1),
        'workout_type' => array('type' => 'string', 'required' => true),
        'workout_intensity' => array('type' => 'int', 'required' => true, 'min' => 1, 'max' => 10),
        'notes' => array('type' => 'string', 'required' => false),
    );

    foreach ($fields as $field => $rules) {
        if (!isset($entry[$field]) && $rules['required']) {
            $errors[] = sprintf(__("Field '%s' is required.", 'athlete-dashboard'), $field);
            continue;
        }

        $value = isset($entry[$field]) ? $entry[$field] : null;

        switch ($rules['type']) {
            case 'date':
                $sanitized_entry[$field] = sanitize_text_field($value);
                if (!wp_checkdate(substr($sanitized_entry[$field], 5, 2), substr($sanitized_entry[$field], 8, 2), substr($sanitized_entry[$field], 0, 4), $sanitized_entry[$field])) {
                    $errors[] = sprintf(__("Invalid date format for '%s'.", 'athlete-dashboard'), $field);
                }
                break;
            case 'int':
                $sanitized_entry[$field] = intval($value);
                if (isset($rules['min']) && $sanitized_entry[$field] < $rules['min']) {
                    $errors[] = sprintf(__("'%s' must be at least %d.", 'athlete-dashboard'), $field, $rules['min']);
                }
                if (isset($rules['max']) && $sanitized_entry[$field] > $rules['max']) {
                    $errors[] = sprintf(__("'%s' must not exceed %d.", 'athlete-dashboard'), $field, $rules['max']);
                }
                break;
            case 'string':
                $sanitized_entry[$field] = sanitize_text_field($value);
                break;
        }
    }

    return array(
        'sanitized_entry' => $sanitized_entry,
        'errors' => $errors
    );
}

/**
 * Retrieve workout log entries for a user
 *
 * @param integer $user_id The ID of the user
 * @param array $args Optional. Arguments to filter the query.
 * @return array The workout log entries
 */
function athlete_dashboard_get_user_workout_logs($user_id, $args = array()) {
    $default_args = array(
        'posts_per_page' => -1,
        'post_type' => 'log_workout',
        'author' => $user_id,
        'orderby' => 'meta_value',
        'meta_key' => '_workout_date',
        'order' => 'DESC',
    );

    $query_args = wp_parse_args($args, $default_args);
    $posts = get_posts($query_args);

    $workout_logs = array();
    foreach ($posts as $post) {
        $workout_logs[] = array(
            'id' => $post->ID,
            'date' => get_post_meta($post->ID, '_workout_date', true),
            'duration' => get_post_meta($post->ID, '_workout_duration', true),
            'type' => get_post_meta($post->ID, '_workout_type', true),
            'intensity' => get_post_meta($post->ID, '_workout_intensity', true),
            'notes' => $post->post_content,
        );
    }

    return $workout_logs;
}

function athlete_dashboard_store_meal_log($user_id, $entry) {
    $meal_datetime = $entry['meal_date'] . ' ' . $entry['meal_time'];
    $post_id = wp_insert_post(array(
        'post_type'    => 'meal_log',
        'post_title'   => sprintf(__('Meal on %s', 'athlete-dashboard'), $meal_datetime),
        'post_content' => $entry['meal_description'],
        'post_status'  => 'publish',
        'post_author'  => $user_id,
    ));

    if (is_wp_error($post_id)) {
        return array('success' => false, 'message' => $post_id->get_error_message());
    }

    $meta_fields = array(
        'meal_date', 'meal_time', 'meal_type', 'meal_name', 'estimated_calories',
        'protein_type', 'protein_quantity', 'protein_unit',
        'fat_type', 'fat_quantity', 'fat_unit',
        'carb_starch_type', 'carb_starch_quantity', 'carb_starch_unit',
        'carb_fruit_type', 'carb_fruit_quantity', 'carb_fruit_unit',
        'carb_vegetable_type', 'carb_vegetable_quantity', 'carb_vegetable_unit'
    );

    foreach ($meta_fields as $field) {
        if (isset($entry[$field])) {
            if ($field === 'estimated_calories') {
                update_post_meta($post_id, '_' . $field, intval($entry[$field]));
            } else {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($entry[$field]));
            }
        }
    }

    return array('success' => true, 'message' => __('Meal log added successfully', 'athlete-dashboard'));
}

function athlete_dashboard_get_user_meal_logs($user_id, $args = array()) {
    $default_args = array(
        'posts_per_page' => -1,
        'post_type'      => 'meal_log',
        'author'         => $user_id,
        'orderby'        => 'meta_value',
        'meta_key'       => '_meal_date',
        'order'          => 'DESC',
    );

    $query_args = wp_parse_args($args, $default_args);
    $posts = get_posts($query_args);
    $meal_logs = array();

    foreach ($posts as $post) {
        $meal_log = array(
            'id'          => $post->ID,
            'date'        => get_post_meta($post->ID, '_meal_date', true),
            'time'        => get_post_meta($post->ID, '_meal_time', true),
            'type'        => get_post_meta($post->ID, '_meal_type', true),
            'name'        => get_post_meta($post->ID, '_meal_name', true),
            'calories'    => get_post_meta($post->ID, '_estimated_calories', true),
            'description' => $post->post_content,
        );

        $nutritional_fields = array(
            'protein', 'fat', 'carb_starch', 'carb_fruit', 'carb_vegetable'
        );

        foreach ($nutritional_fields as $nutrient) {
            $meal_log[$nutrient] = array(
                'type'     => get_post_meta($post->ID, "_{$nutrient}_type", true),
                'quantity' => get_post_meta($post->ID, "_{$nutrient}_quantity", true),
                'unit'     => get_post_meta($post->ID, "_{$nutrient}_unit", true)
            );
        }

        $meal_logs[] = $meal_log;
    }

    return $meal_logs;
}