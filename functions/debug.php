<?php
/**
 * Debug functions for the Athlete Dashboard plugin.
 *
 * This file contains various debugging and logging functions to assist
 * in development and troubleshooting of the Athlete Dashboard plugin.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Log Romanian Deadlift 1RM data for debugging purposes.
 */
function athlete_dashboard_debug_rdl_1rm_data() {
    if (current_user_can('manage_options')) {
        $user_id = get_current_user_id();
        $progress = get_user_meta($user_id, 'rdl_1rm_progress', true);
        athlete_dashboard_log('RDL 1RM Debug: ' . print_r($progress, true));
    }
}
add_action('wp_footer', 'athlete_dashboard_debug_rdl_1rm_data');

/**
 * Check if Divi shortcodes are processed.
 */
function athlete_dashboard_check_divi_shortcodes() {
    if (current_user_can('manage_options')) {
        echo '<div style="display:none;">Divi Shortcodes Processed: ' . (shortcode_exists('et_pb_section') ? 'Yes' : 'No') . '</div>';
    }
}
add_action('wp_footer', 'athlete_dashboard_check_divi_shortcodes');

/**
 * Generic logging function for debug messages.
 *
 * @param mixed $message The message to log.
 * @param string $level The log level (debug, info, warning, error).
 */
function athlete_dashboard_log($message, $level = 'debug') {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        $log_file = WP_CONTENT_DIR . '/athlete-dashboard-debug.log';
        $timestamp = current_time('mysql');
        $formatted_message = "[{$timestamp}] [{$level}] {$message}\n";
        error_log($formatted_message, 3, $log_file);
    }
}

/**
 * Debug function to display all user meta for the current user.
 */
function athlete_dashboard_debug_user_meta() {
    if (current_user_can('manage_options') && isset($_GET['debug_user_meta'])) {
        $user_id = get_current_user_id();
        $user_meta = get_user_meta($user_id);
        echo '<pre>';
        print_r($user_meta);
        echo '</pre>';
        exit;
    }
}
add_action('wp_loaded', 'athlete_dashboard_debug_user_meta');

/**
 * Debug function to display all options related to the Athlete Dashboard.
 */
function athlete_dashboard_debug_options() {
    if (current_user_can('manage_options') && isset($_GET['debug_options'])) {
        global $wpdb;
        $options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'athlete_dashboard_%'");
        echo '<pre>';
        print_r($options);
        echo '</pre>';
        exit;
    }
}
add_action('wp_loaded', 'athlete_dashboard_debug_options');

/**
 * Debug function to log all AJAX requests related to the Athlete Dashboard.
 */
function athlete_dashboard_debug_ajax_requests() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'unknown';
        if (strpos($action, 'athlete_dashboard_') === 0) {
            athlete_dashboard_log("AJAX Request: {$action}", 'info');
            athlete_dashboard_log("AJAX Data: " . print_r($_REQUEST, true), 'debug');
        }
    }
}
add_action('wp_ajax_nopriv_athlete_dashboard_debug_ajax_requests', 'athlete_dashboard_debug_ajax_requests');
add_action('wp_ajax_athlete_dashboard_debug_ajax_requests', 'athlete_dashboard_debug_ajax_requests');

/**
 * Debug function to measure execution time of specific functions.
 *
 * @param callable $function The function to measure.
 * @param array $args The arguments to pass to the function.
 * @return mixed The result of the function call.
 */
function athlete_dashboard_debug_execution_time($function, ...$args) {
    $start_time = microtime(true);
    $result = call_user_func_array($function, $args);
    $end_time = microtime(true);
    $execution_time = $end_time - $start_time;
    athlete_dashboard_log("Execution time for " . (is_array($function) ? get_class($function[0]) . '::' . $function[1] : $function) . ": {$execution_time} seconds", 'info');
    return $result;
}

// Example usage of execution time debugging:
// add_filter('athlete_dashboard_some_filter', function($value) {
//     return athlete_dashboard_debug_execution_time('expensive_function', $value);
// });