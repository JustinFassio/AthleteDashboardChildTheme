<?php
/**
 * Progress Tracking Functions for Athlete Dashboard
 *
 * This file contains functions related to tracking and displaying
 * user progress, including weight and squat performance.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Verify AJAX nonce
 */
function athlete_dashboard_verify_ajax_nonce() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athlete_dashboard_nonce')) {
        wp_send_json_error(['message' => __('Invalid security token. Please refresh the page and try again.', 'athlete-dashboard')]);
    }
}

/**
 * Get progress chart data
 *
 * @param string $progress_type The type of progress (e.g., 'weight', 'squat', 'bench_press')
 * @return array
 */
function athlete_dashboard_get_progress_chart_data($progress_type) {
    error_log("Retrieving $progress_type progress data");
    $user_id = get_current_user_id();
    
    try {
        $progress_data = athlete_dashboard_get_user_progress($user_id, $progress_type);
        error_log("Raw progress data: " . print_r($progress_data, true));
        
        $data = array_map(function($entry) {
            return [
                'x' => date('Y-m-d', strtotime($entry['date'])),
                'y' => floatval($entry['weight'])
            ];
        }, $progress_data);
        
        error_log("Formatted chart data: " . print_r($data, true));
        
        $label = '';
        $border_color = '';

        if ($progress_type === 'weight') {
            $label = __('Body Weight Progress', 'athlete-dashboard');
            $border_color = 'rgb(75, 192, 192)';
        } elseif ($progress_type === 'squat') {
            $label = __('Squat Progress', 'athlete-dashboard');
            $border_color = 'rgb(255, 99, 132)';
        } elseif ($progress_type === 'bench_press') {
            $label = __('Bench Press Progress', 'athlete-dashboard');
            $border_color = 'rgb(54, 162, 235)';
        } elseif ($progress_type === 'deadlift') {
            $label = __('Deadlift Progress', 'athlete-dashboard');
            $border_color = 'rgb(255, 159, 64)';
        }

        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                    'fill' => false,
                    'borderColor' => $border_color,
                    'tension' => 0.1
                ]
            ]
        ];
    } catch (Exception $e) {
        error_log("Error in athlete_dashboard_get_progress_chart_data: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle progress submission
 *
 * @param int $user_id
 * @param float $weight
 * @param string $unit
 * @param string $date
 * @param string $progress_type
 * @return string
 */
function athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, $progress_type) {
    $progress = get_user_meta($user_id, $progress_type . '_progress', true);
    if (!is_array($progress)) {
        $progress = array();
    }
    
    $new_entry = array(
        'date' => $date,
        'weight' => $weight,
        'unit' => $unit
    );
    
    // Check if an entry for this date already exists
    $existing_entry_index = array_search($date, array_column($progress, 'date'));
    
    if ($existing_entry_index !== false) {
        // Replace the existing entry
        $progress[$existing_entry_index] = $new_entry;
    } else {
        // Add the new entry
        $progress[] = $new_entry;
    }
    
    // Sort the progress array by date in descending order
    usort($progress, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    update_user_meta($user_id, $progress_type . '_progress', $progress);
    
    return __('Progress updated successfully', 'athlete-dashboard');
}

/**
 * Get user's progress
 *
 * @param int $user_id
 * @param string $progress_type
 * @return array
 */
function athlete_dashboard_get_user_progress($user_id, $progress_type) {
    $progress = get_user_meta($user_id, $progress_type . '_progress', true);
    return is_array($progress) ? $progress : [];
}

/**
 * Get user's weight progress
 */
function athlete_dashboard_get_user_weight_progress() {
    error_log('athlete_dashboard_get_user_weight_progress called');
    athlete_dashboard_verify_ajax_nonce();
    
    try {
        $progress = athlete_dashboard_get_progress_chart_data('weight');
        error_log('Weight progress data: ' . print_r($progress, true));
        wp_send_json_success($progress);
    } catch (Exception $e) {
        error_log('Error in athlete_dashboard_get_user_weight_progress: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error retrieving weight progress data']);
    }
}
add_action('wp_ajax_athlete_dashboard_get_user_progress', 'athlete_dashboard_get_user_weight_progress');

/**
 * Handle user's squat progress
 */
function athlete_dashboard_handle_squat_progress_submission($user_id, $weight, $unit, $date) {
    return athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, 'squat');
}

/**
 * Get user's squat progress
 */
function athlete_dashboard_get_user_squat_progress() {
    error_log('athlete_dashboard_get_user_squat_progress called');
    athlete_dashboard_verify_ajax_nonce();
    
    $progress = athlete_dashboard_get_progress_chart_data('squat');
    
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_squat_progress', 'athlete_dashboard_get_user_squat_progress');

/**
 * Handle user's bench press progress
 */
function athlete_dashboard_handle_bench_press_progress_submission($user_id, $weight, $unit, $date) {
    return athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, 'bench_press');
}

/**
 * Handle user's deadlift progress
 */
function athlete_dashboard_handle_deadlift_progress_submission($user_id, $weight, $unit, $date) {
    return athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, 'deadlift');
}

/**
 * Get user's bench press progress
 */
function athlete_dashboard_get_user_bench_press_progress() {
    error_log('athlete_dashboard_get_user_bench_press_progress called');
    athlete_dashboard_verify_ajax_nonce();
    
    $progress = athlete_dashboard_get_progress_chart_data('bench_press');
    
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_bench_press_progress', 'athlete_dashboard_get_user_bench_press_progress');

/**
 * Get user's deadlift progress
 */
function athlete_dashboard_get_user_deadlift_progress() {
    error_log('athlete_dashboard_get_user_deadlift_progress called');
    athlete_dashboard_verify_ajax_nonce();
    
    $progress = athlete_dashboard_get_progress_chart_data('deadlift');
    
    wp_send_json_success($progress);
}
add_action('wp_ajax_athlete_dashboard_get_user_deadlift_progress', 'athlete_dashboard_get_user_deadlift_progress');


/**
 * Display progress chart shortcode
 *
 * @param string $progress_type
 * @return string
 */
function athlete_dashboard_display_progress_chart($progress_type) {
    if (!is_user_logged_in()) {
        return __('Please log in to view your progress.', 'athlete-dashboard');
    }

    $user_id = get_current_user_id();
    $progress_data = athlete_dashboard_get_user_progress($user_id, $progress_type);

    if (empty($progress_data)) {
        return __('No progress data available.', 'athlete-dashboard');
    }

    $chart_data = athlete_dashboard_get_progress_chart_data($progress_type);

    $chart_options = [
        'responsive' => true,
        'scales' => [
            'y' => [
                'beginAtZero' => false
            ]
        ]
    ];

    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.1', true);

    $canvas_id = $progress_type . '-progress-chart-' . uniqid();

    $output = '<canvas id="' . esc_attr($canvas_id) . '"></canvas>';
    $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("' . esc_js($canvas_id) . '").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: ' . wp_json_encode($chart_data) . ',
                options: ' . wp_json_encode($chart_options) . '
            });
        });
    </script>';

    return $output;
}

/**
 * Display weight progress chart shortcode
 *
 * @return string
 */
function athlete_dashboard_display_weight_progress_chart() {
    return athlete_dashboard_display_progress_chart('weight');
}
add_shortcode('athlete_dashboard_weight_progress_chart', 'athlete_dashboard_display_weight_progress_chart');

/**
 * Display squat progress chart shortcode
 *
 * @return string
 */
function athlete_dashboard_display_squat_progress_chart() {
    return athlete_dashboard_display_progress_chart('squat');
}
add_shortcode('athlete_dashboard_squat_progress_chart', 'athlete_dashboard_display_squat_progress_chart');

/**
 * Display bench press progress chart shortcode
 *
 * @return string
 */
function athlete_dashboard_display_bench_press_progress_chart() {
    return athlete_dashboard_display_progress_chart('bench_press');
}
add_shortcode('athlete_dashboard_bench_press_progress_chart', 'athlete_dashboard_display_bench_press_progress_chart');

/**
 * Display deadlift progress chart shortcode
 *
 * @return string
 */
function athlete_dashboard_display_deadlift_progress_chart() {
    return athlete_dashboard_display_progress_chart('deadlift');
}
add_shortcode('athlete_dashboard_deadlift_progress_chart', 'athlete_dashboard_display_deadlift_progress_chart');

/**
 * Get comprehensive body composition progress
 */
function athlete_dashboard_get_comprehensive_body_composition_progress() {
    athlete_dashboard_verify_ajax_nonce();
    
    $user_id = get_current_user_id();
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
    
    $progress = get_user_meta($user_id, 'body_composition_progress', true);
    $progress = is_array($progress) ? $progress : [];
    
    // Filter progress based on start and end dates if provided
    if ($start_date && $end_date) {
        $progress = array_filter($progress, function($entry) use ($start_date, $end_date) {
            $entry_date = strtotime($entry['date']);
            return $entry_date >= strtotime($start_date) && $entry_date <= strtotime($end_date);
        });
    }
    
    // Prepare data for chart
    $labels = [];
    $weight_data = [];
    $body_fat_data = [];
    $muscle_mass_data = [];
    $bmi_data = [];
    
    foreach ($progress as $entry) {
        $labels[] = $entry['date'];
        $weight_data[] = $entry['weight'];
        $body_fat_data[] = $entry['body_fat_percentage'];
        $muscle_mass_data[] = $entry['muscle_mass'];
        $bmi_data[] = $entry['bmi'];
    }
    
    $data = [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => __('Weight (kg)', 'athlete-dashboard'),
                'data' => $weight_data,
                'borderColor' => 'rgb(75, 192, 192)',
                'yAxisID' => 'y-axis-1'
            ],
            [
                'label' => __('Body Fat (%)', 'athlete-dashboard'),
                'data' => $body_fat_data,
                'borderColor' => 'rgb(255, 99, 132)',
                'yAxisID' => 'y-axis-2'
            ],
            [
                'label' => __('Muscle Mass (kg)', 'athlete-dashboard'),
                'data' => $muscle_mass_data,
                'borderColor' => 'rgb(54, 162, 235)',
                'yAxisID' => 'y-axis-1'
            ],
            [
                'label' => __('BMI', 'athlete-dashboard'),
                'data' => $bmi_data,
                'borderColor' => 'rgb(255, 206, 86)',
                'yAxisID' => 'y-axis-2'
            ]
        ]
    ];
    
    wp_send_json_success($data);
}
add_action('wp_ajax_athlete_dashboard_get_comprehensive_body_composition_progress', 'athlete_dashboard_get_comprehensive_body_composition_progress');

/**
 * Store comprehensive body composition progress
 */
function athlete_dashboard_store_comprehensive_body_composition_progress() {
    athlete_dashboard_verify_ajax_nonce();
    
    $user_id = get_current_user_id();
    $weight = floatval($_POST['weight']);
    $body_fat_percentage = floatval($_POST['body_fat_percentage']);
    $muscle_mass = floatval($_POST['muscle_mass']);
    $bmi = floatval($_POST['bmi']);
    $date = sanitize_text_field($_POST['date']);
    
    $progress = get_user_meta($user_id, 'body_composition_progress', true);
    $progress = is_array($progress) ? $progress : [];
    
    $new_entry = [
        'date' => $date,
        'weight' => $weight,
        'body_fat_percentage' => $body_fat_percentage,
        'muscle_mass' => $muscle_mass,
        'bmi' => $bmi
    ];
    
    // Add new entry or update existing one for the same date
    $existing_entry_index = array_search($date, array_column($progress, 'date'));
    if ($existing_entry_index !== false) {
        $progress[$existing_entry_index] = $new_entry;
    } else {
        $progress[] = $new_entry;
    }
    
    // Sort progress by date, newest first
    usort($progress, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    update_user_meta($user_id, 'body_composition_progress', $progress);
    
    wp_send_json_success(['message' => __('Body composition progress updated successfully', 'athlete-dashboard')]);
}
add_action('wp_ajax_athlete_dashboard_store_comprehensive_body_composition_progress', 'athlete_dashboard_store_comprehensive_body_composition_progress');

/**
 * Display comprehensive body composition progress chart shortcode
 *
 * @return string
 */
function athlete_dashboard_display_comprehensive_body_composition_chart() {
    if (!is_user_logged_in()) {
        return __('Please log in to view your progress.', 'athlete-dashboard');
    }

    $user_id = get_current_user_id();
    $progress = get_user_meta($user_id, 'body_composition_progress', true);

    if (empty($progress)) {
        return __('No body composition data available.', 'athlete-dashboard');
    }

    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.1', true);

    $canvas_id = 'body-composition-chart-' . uniqid();

    $output = '<canvas id="' . esc_attr($canvas_id) . '"></canvas>';
    $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("' . esc_js($canvas_id) . '").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: ' . wp_json_encode(athlete_dashboard_get_comprehensive_body_composition_data($progress)) . ',
                options: {
                    responsive: true,
                    scales: {
                        "y-axis-1": {
                            type: "linear",
                            display: true,
                            position: "left",
                            title: {
                                display: true,
                                text: "' . esc_js(__('Weight / Muscle Mass (kg)', 'athlete-dashboard')) . '"
                            }
                        },
                        "y-axis-2": {
                            type: "linear",
                            display: true,
                            position: "right",
                            title: {
                                display: true,
                                text: "' . esc_js(__('Body Fat (%) / BMI', 'athlete-dashboard')) . '"
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        });
    </script>';

    return $output;
}
add_shortcode('athlete_dashboard_comprehensive_body_composition_chart', 'athlete_dashboard_display_comprehensive_body_composition_chart');

/**
 * Prepare comprehensive body composition data for chart
 *
 * @param array $progress
 * @return array
 */
function athlete_dashboard_get_comprehensive_body_composition_data($progress) {
    $labels = [];
    $weight_data = [];
    $body_fat_data = [];
    $muscle_mass_data = [];
    $body_mass_index_data = [];
    
    foreach ($progress as $entry) {
        $labels[] = $entry['date'];
        $weight_data[] = $entry['weight'];
        $body_fat_data[] = $entry['body_fat_percentage'];
        $muscle_mass_data[] = $entry['muscle_mass'];
        $body_mass_index_data[] = $entry['bmi'];
    }
    
    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => __('Weight (kg)', 'athlete-dashboard'),
                'data' => $weight_data,
                'borderColor' => 'rgb(75, 192, 192)',
                'yAxisID' => 'y-axis-1'
            ],
            [
                'label' => __('Body Fat (%)', 'athlete-dashboard'),
                'data' => $body_fat_data,
                'borderColor' => 'rgb(255, 99, 132)',
                'yAxisID' => 'y-axis-2'
            ],
            [
                'label' => __('Muscle Mass (kg)', 'athlete-dashboard'),
                'data' => $muscle_mass_data,
                'borderColor' => 'rgb(54, 162, 235)',
                'yAxisID' => 'y-axis-1'
            ],
            [
                'label' => __('Body Mass Index', 'athlete-dashboard'),
                'data' => $body_mass_index_data,
                'borderColor' => 'rgb(255, 206, 86)',
                'yAxisID' => 'y-axis-2'
            ]
        ]
    ];
}

/**
 * Handle progress submission for weight, squat, and bench press
 */
function athlete_dashboard_handle_generic_progress_submission() {
    athlete_dashboard_verify_ajax_nonce();
    
    $user_id = get_current_user_id();
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $unit = isset($_POST['weight_unit']) ? sanitize_text_field($_POST['weight_unit']) : 'kg';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('mysql');
    $progress_type = isset($_POST['progress_type']) ? sanitize_text_field($_POST['progress_type']) : 'weight';
    
    $result = athlete_dashboard_handle_progress_submission($user_id, $weight, $unit, $date, $progress_type);
    
    if ($result) {
        wp_send_json_success(['message' => sprintf(__('%s progress updated successfully', 'athlete-dashboard'), ucfirst($progress_type))]);
    } else {
        wp_send_json_error(['message' => sprintf(__('Failed to update %s progress', 'athlete-dashboard'), $progress_type)]);
    }
}
add_action('wp_ajax_athlete_dashboard_handle_generic_progress_submission', 'athlete_dashboard_handle_generic_progress_submission');

/**
 * Get the most recent progress entry for a user
 *
 * @param int $user_id
 * @param string $progress_type
 * @return string
 */
function athlete_dashboard_get_most_recent_progress($user_id, $progress_type) {
    $progress = get_user_meta($user_id, $progress_type . '_progress', true);
    
    if (!is_array($progress) || empty($progress)) {
        return __('No data', 'athlete-dashboard');
    }
    
    $latest_entry = reset($progress);
    
    if (!isset($latest_entry['weight']) || !isset($latest_entry['unit'])) {
        return __('Invalid data', 'athlete-dashboard');
    }
    
    if (!is_numeric($latest_entry['weight'])) {
        return __('Invalid data', 'athlete-dashboard');
    }
    
    $decimal_places = 2;
    $formatted_weight = number_format($latest_entry['weight'], $decimal_places);
    
    return $formatted_weight . ' ' . $latest_entry['unit'];
}

/**
 * Get the most recent weight entry for a user
 *
 * @param int $user_id
 * @return string
 */
function athlete_dashboard_get_most_recent_weight($user_id) {
    return athlete_dashboard_get_most_recent_progress($user_id, 'weight');
}

/**
 * Get the most recent squat weight entry for a user
 *
 * @param int $user_id
 * @return string
 */
function athlete_dashboard_get_most_recent_squat_weight($user_id) {
    return athlete_dashboard_get_most_recent_progress($user_id, 'squat');
}

/**
 * Get the most recent bench press weight entry for a user
 *
 * @param int $user_id
 * @return string
 */
function athlete_dashboard_get_most_recent_bench_press_weight($user_id) {
    return athlete_dashboard_get_most_recent_progress($user_id, 'bench_press');
}

/**
 * Get the most recent deadlift weight entry for a user
 *
 * @param int $user_id
 * @return string
 */
function athlete_dashboard_get_most_recent_deadlift_weight($user_id) {
    return athlete_dashboard_get_most_recent_progress($user_id, 'deadlift');
}

/**
 * Check the data structure of the progress for a user
 *
 * @param int $user_id
 * @param string $progress_type
 */
function athlete_dashboard_check_progress_data_structure($user_id, $progress_type) {
    $progress = get_user_meta($user_id, $progress_type . '_progress', true);
    error_log($progress_type . ' Progress Data Structure Check for user ' . $user_id . ':');
    error_log('Is array: ' . (is_array($progress) ? 'Yes' : 'No'));
    error_log('Count: ' . (is_array($progress) ? count($progress) : 'Not Applicable'));
    error_log('Full data: ' . print_r($progress, true));
}

/**
 * Check the data structure of the weight progress for a user
 *
 * @param int $user_id
 */
function athlete_dashboard_check_weight_data_structure($user_id) {
    athlete_dashboard_check_progress_data_structure($user_id, 'weight');
}

/**
 * Check the data structure of the squat progress for a user
 *
 * @param int $user_id
 */
function athlete_dashboard_check_squat_data_structure($user_id) {
    athlete_dashboard_check_progress_data_structure($user_id, 'squat');
}

/**
 * Check the data structure of the bench press progress for a user
 *
 * @param int $user_id
 */
function athlete_dashboard_check_bench_press_data_structure($user_id) {
    athlete_dashboard_check_progress_data_structure($user_id, 'bench_press');
}

/**
 * Check the data structure of the deadlift progress for a user
 *
 * @param int $user_id
 */
function athlete_dashboard_check_deadlift_data_structure($user_id) {
    athlete_dashboard_check_progress_data_structure($user_id, 'deadlift');
}