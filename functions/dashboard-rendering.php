<?php
/**
 * Dashboard Rendering Functions for Athlete Dashboard
 *
 * This file contains the main rendering function and shortcode registration
 * for the Athlete Dashboard plugin.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Render the Athlete Dashboard.
 *
 * @return string The HTML content of the dashboard.
 */
function athlete_dashboard_render_dashboard() {
    if (!is_user_logged_in()) {
        return athlete_dashboard_render_login_message();
    }
    
    $current_user = wp_get_current_user();
    ob_start();
    ?>
    <div class="athlete-dashboard-container">
        <?php athlete_dashboard_render_welcome_banner($current_user); ?>
        <div class="athlete-dashboard">
            <?php echo athlete_dashboard_render_all_sections(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('athlete_dashboard', 'athlete_dashboard_render_dashboard');

function athlete_dashboard_render_all_sections() {
    ob_start();
    ?>
    <div class="dashboard-content">
        <?php
        athlete_dashboard_render_section('overview', __('Overview', 'athlete-dashboard'), '[user_overview]', 'full-width');
        
        // Group A: Workout Journey
        ?>
        <div class="dashboard-group collapsible-group" data-group-name="Workout Journey">
            <div class="group-header">
                <h2><?php esc_html_e('Train, Sleep, Repeat', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Workout Journey', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <div class="workout-list-scrollable">
                    <?php
                    athlete_dashboard_render_section('workouts', __('Current Workout', 'athlete-dashboard'), '[user_workouts]', 'full-width');
                    ?>
                </div>
                <?php
                athlete_dashboard_render_section('log-workout', __('Log Workout', 'athlete-dashboard'), 'athlete_dashboard_log_workout_content', 'full-width');
                athlete_dashboard_render_section('upcoming-workouts', __('Upcoming Workouts', 'athlete-dashboard'), '[user_upcoming_workouts]', 'full-width');
                ?>
            </div>
        </div>
        
        <!-- Group B: Progress Tracking -->
        <div class="dashboard-group collapsible-group" data-group-name="Progress Tracking">
            <div class="group-header">
                <h2><?php esc_html_e('Your Roadmap to Success', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Progress Tracking', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <?php
                athlete_dashboard_render_section('progress', __('Body Weight Progress', 'athlete-dashboard'), 'athlete_dashboard_body_weight_progress_content', 'full-width');
                athlete_dashboard_render_section('comprehensive-body-composition', __('Body Composition', 'athlete-dashboard'), 'athlete_dashboard_comprehensive_body_composition_content', 'full-width');
                ?>
            </div>
        </div>
        
        <!-- Group C: Strength Milestones -->
        <div class="dashboard-group collapsible-group" data-group-name="Strength Milestones">
            <div class="group-header">
                <h2><?php esc_html_e('Crushing Personal Records', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Strength Milestones', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <?php
                athlete_dashboard_render_section('squat-progress', __('Squat Progress', 'athlete-dashboard'), 'athlete_dashboard_squat_progress_content', 'full-width');    
                athlete_dashboard_render_section('bench-press-progress', __('Bench Press Progress', 'athlete-dashboard'), 'athlete_dashboard_bench_press_progress_content', 'full-width');
                athlete_dashboard_render_section('deadlift-progress', __('Deadlift Progress', 'athlete-dashboard'), 'athlete_dashboard_deadlift_progress_content', 'full-width');
                ?>
            </div>
        </div>
        
        <!-- Group D: Performance Metrics -->
        <div class="dashboard-group collapsible-group" data-group-name="Performance Metrics">
            <div class="group-header">
                <h2><?php esc_html_e('Benchmark Your Beast Mode', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Performance Metrics', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <?php
                athlete_dashboard_render_section('exercise-progress', __('Benchmark Tests', 'athlete-dashboard'), 'athlete_dashboard_exercise_progress_content', 'full-width');
                ?>
            </div>
        </div>
        
        <!-- Group E: Nutrition Insights -->
        <div class="dashboard-group collapsible-group" data-group-name="Nutrition Insights">
            <div class="group-header">
                <h2><?php esc_html_e('Fuel Your Fire', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Nutrition Insights', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <div class="nutrition-list-scrollable">
                    <?php
                    athlete_dashboard_render_section('nutrition', __('Nutrition', 'athlete-dashboard'), '[user_nutrition]', 'full-width');
                    ?>
                </div>
                <?php
                athlete_dashboard_render_section('log-meal', __('Log Meal', 'athlete-dashboard'), 'athlete_dashboard_meal_log_content', 'full-width');
                ?>
            </div>
        </div>
        
        <!-- Group F: Member Dashboard & Insights -->
        <div class="dashboard-group collapsible-group" data-group-name="Member Dashboard & Insights">
            <div class="group-header">
                <h2><?php esc_html_e('Member Engagement & Progress', 'athlete-dashboard'); ?></h2>
                <button class="toggle-group" aria-expanded="true">
                    <span class="screen-reader-text"><?php esc_html_e('Toggle Member Dashboard & Insights', 'athlete-dashboard'); ?></span>
                    <span class="fa fa-chevron-up" aria-hidden="true"></span>
                    <span class="fa fa-chevron-down" aria-hidden="true" style="display:none;"></span>
                </button>
            </div>
            <div class="group-content">
                <?php
                athlete_dashboard_render_section('personal-training-sessions', __('Personal Training Sessions', 'athlete-dashboard'), 'athlete_dashboard_personal_training_sessions_content', 'full-width');
                athlete_dashboard_render_section('class-bookings', __('Class Bookings', 'athlete-dashboard'), 'athlete_dashboard_class_bookings_content', 'full-width');
                athlete_dashboard_render_section('membership-status', __('Membership Status', 'athlete-dashboard'), 'athlete_dashboard_membership_status_content', 'full-width');
                athlete_dashboard_render_section('check-ins-attendance', __('Check-Ins and Attendance', 'athlete-dashboard'), 'athlete_dashboard_check_ins_attendance_content', 'full-width');
                athlete_dashboard_render_section('goal-tracking-progress', __('Goal Tracking and Progress', 'athlete-dashboard'), 'athlete_dashboard_goal_tracking_progress_content', 'full-width');
                athlete_dashboard_render_section('personalized-recommendations', __('Personalized Recommendations', 'athlete-dashboard'), 'athlete_dashboard_personalized_recommendations_content', 'full-width');
                ?>
            </div>
        </div>
        <?php
        // Remaining ungrouped sections
        athlete_dashboard_render_section('fitness-plan', __('Fitness Plan', 'athlete-dashboard'), '[user_fitness_plan]', 'full-width');
        athlete_dashboard_render_section('messaging', __('Messages', 'athlete-dashboard'), 'athlete_dashboard_render_messaging_preview', 'full-width');
        athlete_dashboard_render_section('account-details', __('Account Details', 'athlete-dashboard'), 'athlete_dashboard_account_details_content', 'full-width');
        ?>
    </div>
    <?php
    return ob_get_clean();
}
