<?php
/**
 * Exercise Data Functions for Athlete Dashboard
 *
 * This file contains functions related to exercise data management,
 * including progress tracking and data retrieval for specific exercises.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get exercise tests data
 *
 * @return array
 */
function athlete_dashboard_get_exercise_tests() {
    return array(
        '5k_run' => array('label' => '5k Run', 'unit' => 'minutes', 'decimal_places' => 2, 'bilateral' => false),
        '20k_cycling' => array('label' => '20k Cycling', 'unit' => 'minutes', 'decimal_places' => 2, 'bilateral' => false),
        '10k_rucking' => array('label' => '10k Rucking', 'unit' => 'minutes', 'decimal_places' => 2, 'bilateral' => false),
        '400m_swim' => array('label' => '400m Swim', 'unit' => 'seconds', 'decimal_places' => 1, 'bilateral' => false),
        'slrdl' => array('label' => 'Single-Leg Romanian Deadlift', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => true),
        'pistol_squat' => array('label' => 'Single-Leg Squat', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => true),
        'pushups' => array('label' => 'Push-Ups', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => false),
        'pullups' => array('label' => 'Pull-Ups', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => false),
        'vertical_jump' => array('label' => 'Vertical Jump', 'unit' => 'inches', 'decimal_places' => 1, 'bilateral' => true),
        'sit_reach' => array('label' => 'Sit-and-Reach', 'unit' => 'inches', 'decimal_places' => 1, 'bilateral' => false),
        'balance_test' => array('label' => 'Single-Leg Balance', 'unit' => 'seconds', 'decimal_places' => 1, 'bilateral' => true),
        'farmers_walk' => array('label' => 'Loaded Carry', 'unit' => 'meters', 'decimal_places' => 1, 'bilateral' => false),
        'burpee_test' => array('label' => 'Burpee Test', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => false),
        'deadhang' => array('label' => 'Deadhang', 'unit' => 'seconds', 'decimal_places' => 1, 'bilateral' => false),
        'plank' => array('label' => 'Plank', 'unit' => 'seconds', 'decimal_places' => 1, 'bilateral' => false),
        'situps' => array('label' => 'Sit-Ups', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => false),
        '1k_walk' => array('label' => '1K Walk', 'unit' => 'minutes', 'decimal_places' => 2, 'bilateral' => false),
        'body_weight_squats' => array('label' => 'Body Weight Squats', 'unit' => 'reps', 'decimal_places' => 0, 'bilateral' => false),
    );
    error_log("Exercise tests defined: " . print_r($tests, true));
    return $tests;
}

/**
 * Handle exercise progress submission
 */
function athlete_dashboard_handle_exercise_progress_submission() {
    athlete_dashboard_verify_ajax_nonce();
    
    $user_id = get_current_user_id();
    $exercise_key = sanitize_text_field($_POST['exercise_key']);
    $value = floatval($_POST['value']);
    $date = sanitize_text_field($_POST['date']);
    
    $result = athlete_dashboard_update_exercise_progress($user_id, $exercise_key, $value, $date);
    
    if ($result) {
        wp_send_json_success(['message' => __('Progress updated successfully', 'athlete-dashboard')]);
    } else {
        wp_send_json_error(['message' => __('Failed to update progress', 'athlete-dashboard')]);
    }
}
add_action('wp_ajax_athlete_dashboard_handle_exercise_progress_submission', 'athlete_dashboard_handle_exercise_progress_submission');

/**
 * Get exercise progress
 */
function athlete_dashboard_get_exercise_progress() {
    error_log("athlete_dashboard_get_exercise_progress called");

    try {
        athlete_dashboard_verify_ajax_nonce();
        
        $user_id = get_current_user_id();
        $exercise_key = isset($_POST['exercise_key']) ? sanitize_text_field($_POST['exercise_key']) : '';

        error_log("Fetching progress for user ID: $user_id, exercise key: $exercise_key");

        if (empty($exercise_key)) {
            throw new Exception("Exercise key is missing");
        }

        $exercise_tests = athlete_dashboard_get_exercise_tests();
        if (!isset($exercise_tests[$exercise_key])) {
            throw new Exception("Invalid exercise key: $exercise_key");
        }

        $is_bilateral = isset($exercise_tests[$exercise_key]['bilateral']) && $exercise_tests[$exercise_key]['bilateral'];
        error_log("Exercise is bilateral: " . ($is_bilateral ? "Yes" : "No"));

        $progress = athlete_dashboard_get_exercise_progress_by_key($user_id, $exercise_key);
        error_log("Raw progress data: " . print_r($progress, true));

        // Process the progress data for chart display
        $chart_data = athlete_dashboard_get_exercise_chart_data($progress, $exercise_tests[$exercise_key]['label'], $is_bilateral);
        error_log("Processed chart data: " . print_r($chart_data, true));

        wp_send_json_success($chart_data);
    } catch (Exception $e) {
        error_log("Error in athlete_dashboard_get_exercise_progress: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_athlete_dashboard_get_exercise_progress', 'athlete_dashboard_get_exercise_progress');

/**
 * Get exercise progress by key
 *
 * @param int $user_id
 * @param string $exercise_key
 * @return array
 */
function athlete_dashboard_get_exercise_progress_by_key($user_id, $exercise_key) {
    $progress = get_user_meta($user_id, "{$exercise_key}_progress", true);
    $exercise_tests = athlete_dashboard_get_exercise_tests();

    if (!is_array($progress)) {
        return [];
    }

    if ($exercise_tests[$exercise_key]['bilateral']) {
        foreach ($progress as &$entry) {
            if (!isset($entry['left']) && !isset($entry['right'])) {
                // Convert old format to new bilateral format
                $entry = [
                    'date' => $entry['date'],
                    'left' => $entry['value'],
                    'right' => $entry['value'],
                    'unit' => $entry['unit']
                ];
            }
        }
    }

    return $progress;
}

/**
 * Update exercise progress
 *
 * @param int $user_id
 * @param string $exercise_key
 * @param float $value
 * @param string $date
 * @param string $unit
 * @param string $side Optional. 'left', 'right', or null for non-bilateral exercises.
 * @return bool
 */
function athlete_dashboard_update_exercise_progress($user_id, $exercise_key, $value, $date, $unit = '', $side = null) {
    $progress = athlete_dashboard_get_exercise_progress_by_key($user_id, $exercise_key);
    $exercise_tests = athlete_dashboard_get_exercise_tests();

    $new_entry = [
        'date' => $date,
        'unit' => $unit
    ];

    if ($exercise_tests[$exercise_key]['bilateral'] && $side) {
        $new_entry[$side] = $value;
    } else {
        $new_entry['value'] = $value;
    }

    $existing_entry_index = array_search($date, array_column($progress, 'date'));
    if ($existing_entry_index !== false) {
        if ($exercise_tests[$exercise_key]['bilateral'] && $side) {
            $progress[$existing_entry_index][$side] = $value;
        } else {
            $progress[$existing_entry_index] = $new_entry;
        }
    } else {
        $progress[] = $new_entry;
    }

    usort($progress, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    $update_result = update_user_meta($user_id, "{$exercise_key}_progress", $progress);
    update_user_meta($user_id, $exercise_key, $value);

    return $update_result;
}

/**
 * Get exercise chart data
 *
 * @param array $progress
 * @param string $label
 * @param bool $bilateral
 * @return array
 */
function athlete_dashboard_get_exercise_chart_data($progress, $label, $bilateral = false) {
    if ($bilateral) {
        $left_data = array_map(function($entry) {
            return [
                'x' => $entry['date'],
                'y' => isset($entry['left']) ? floatval($entry['left']) : null
            ];
        }, $progress);
        $right_data = array_map(function($entry) {
            return [
                'x' => $entry['date'],
                'y' => isset($entry['right']) ? floatval($entry['right']) : null
            ];
        }, $progress);
        return [
            'datasets' => [
                [
                    'label' => $label . ' (Left)',
                    'data' => $left_data,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ],
                [
                    'label' => $label . ' (Right)',
                    'data' => $right_data,
                    'fill' => false,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1
                ]
            ]
        ];
    } else {
        $data = array_map(function($entry) {
            return [
                'x' => $entry['date'],
                'y' => floatval($entry['value'])
            ];
        }, $progress);
        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ];
    }
}

/**
 * Migrate bilateral exercise data
 */
function athlete_dashboard_migrate_bilateral_exercise_data() {
    $users = get_users();
    $exercise_tests = athlete_dashboard_get_exercise_tests();

    foreach ($users as $user) {
        foreach ($exercise_tests as $exercise_key => $test) {
            if ($test['bilateral']) {
                $progress = get_user_meta($user->ID, "{$exercise_key}_progress", true);
                if (is_array($progress)) {
                    $updated_progress = [];
                    foreach ($progress as $entry) {
                        if (!isset($entry['left']) && !isset($entry['right'])) {
                            $updated_progress[] = [
                                'date' => $entry['date'],
                                'left' => $entry['value'],
                                'right' => $entry['value'],
                                'unit' => $entry['unit']
                            ];
                        } else {
                            $updated_progress[] = $entry;
                        }
                    }
                    update_user_meta($user->ID, "{$exercise_key}_progress", $updated_progress);
                }
            }
        }
    }
}

// Don't forget to call this function once to migrate existing data
// add_action('init', 'athlete_dashboard_migrate_bilateral_exercise_data');
