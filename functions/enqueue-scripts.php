<?php
// functions/enqueue-scripts.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('divi_child_enqueue_styles_and_scripts')) {
    function divi_child_enqueue_styles_and_scripts() {
        // Enqueue styles
        enqueue_styles();
        
        // Enqueue scripts
        enqueue_scripts();
        
        // Localize script
        localize_script();
    }
}

function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

function enqueue_styles() {
    // Enqueue parent (Divi) style
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Enqueue variables.css
    wp_enqueue_style(
        'variables-style',
        get_stylesheet_directory_uri() . '/variables.css',
        array(),
        wp_get_theme()->get('Version')
    );
    
    // Enqueue child style with dependency on parent style and variables
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style', 'variables-style'),
        wp_get_theme()->get('Version')
    );
    
    // Enqueue custom CSS file
    wp_enqueue_style(
        'custom-styles', 
        get_stylesheet_directory_uri() . '/custom-styles.css', 
        array('parent-style'), 
        wp_get_theme()->get('Version')
    );

    // Enqueue jQuery UI CSS
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}

function enqueue_scripts() {
    // Enqueue jQuery and its dependencies
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('jquery-effects-core');
    
    // Enqueue Chart.js
    wp_enqueue_script(
        'chartjs', 
        'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js', 
        array(), 
        '4.3.0', 
        true
    );
    
    // Enqueue Chart.js Adapter
    wp_enqueue_script(
        'chartjs-adapter-date-fns',
        'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js',
        array('chartjs'),
        '3.0.0',
        true
    );
    
    // Enqueue custom JS file with dynamic version
    $custom_js_file = get_stylesheet_directory() . '/js/custom-scripts.js';
    $custom_js_version = file_exists($custom_js_file) ? filemtime($custom_js_file) : '1.0';
    wp_enqueue_script('custom-scripts', get_stylesheet_directory_uri() . '/js/custom-scripts.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-effects-core', 'chartjs', 'chartjs-adapter-date-fns'), $custom_js_version, true);

    // Enqueue messaging JS file
    $messaging_js_file = get_stylesheet_directory() . '/js/messaging.js';
    $messaging_js_version = file_exists($messaging_js_file) ? filemtime($messaging_js_file) : '1.0';
    wp_enqueue_script(
        'messaging-scripts',
        get_stylesheet_directory_uri() . '/js/messaging.js',
        array('jquery'),
        $messaging_js_version,
        true
    );
}

// Make sure this function is called
add_action('wp_enqueue_scripts', 'enqueue_scripts');
add_action('admin_enqueue_scripts', 'enqueue_scripts');

function localize_script() {
    wp_localize_script('custom-scripts', 'athleteDashboard', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('athlete_dashboard_nonce'),
        'exerciseTests' => function_exists('get_exercise_tests') ? get_exercise_tests() : array(),
        'current_user_id' => get_current_user_id()
    ));

    // Localize the messaging script
    wp_localize_script('messaging-scripts', 'athleteDashboardMessaging', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('athlete_dashboard_messaging_nonce'),
        'admin_nonce' => wp_create_nonce('athlete_dashboard_admin_nonce'),
        'current_user_id' => get_current_user_id(),
        'is_admin' => current_user_can('edit_users') || in_array('author', wp_get_current_user()->roles)
    ));
}

// Hook the main function to the wp_enqueue_scripts action
add_action('wp_enqueue_scripts', 'divi_child_enqueue_styles_and_scripts', 20);

/**
 * Enqueue styles and scripts for the Athlete Dashboard.
 */
function athlete_dashboard_enqueue_styles_and_scripts() {
    // This function is now empty as its contents have been merged into the existing functions above.
    // We keep it here for backward compatibility in case it's called elsewhere in the theme.
}
add_action('wp_enqueue_scripts', 'athlete_dashboard_enqueue_styles_and_scripts', 21);

// Add the Stripe.js enqueue function
function enqueue_stripe_js() {
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/buy-button.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_stripe_js');

/**
 * Enqueue reCAPTCHA v3 script
 */
function enqueue_recaptcha_v3_script() {
    $recaptcha_site_key = '6Lc1Ly0qAAAAAF37K-Y8vkcCJQsiPrGADWD4T137';
    wp_enqueue_script('recaptcha-v3', "https://www.google.com/recaptcha/api.js?render=$recaptcha_site_key", array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_recaptcha_v3_script');
