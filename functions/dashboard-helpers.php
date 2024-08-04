<?php
/**
 * Dashboard Helper Functions for Athlete Dashboard
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('athlete_dashboard_render_welcome_banner')) {
    /**
     * Render the welcome banner.
     *
     * @param WP_User $current_user The current user object.
     */
    function athlete_dashboard_render_welcome_banner($current_user) {
        ?>
        <div class="welcome-banner" id="welcomeBanner">
            <!-- Welcome banner content -->
        </div>
        <?php
    }
}

if (!function_exists('athlete_dashboard_render_section')) {
    /**
     * Render a dashboard section.
     *
     * @param string $id Section ID.
     * @param string $title Section title.
     * @param string $content_callback Content callback function or shortcode.
     * @param string $width Section width class.
     */
    function athlete_dashboard_render_section($id, $title, $content_callback, $width) {
        ?>
        <div id="<?php echo esc_attr($id); ?>" class="dashboard-section <?php echo esc_attr($width); ?>">
            <!-- Section content -->
        </div>
        <?php
    }
}

if (!function_exists('athlete_dashboard_render_login_message')) {
    /**
     * Render login message for non-logged-in users.
     */
    function athlete_dashboard_render_login_message() {
        ?>
        <p>
            <?php
            printf(
                /* translators: %s: login URL */
                wp_kses(
                    __('Please <a href="%s">log in</a> to view your dashboard.', 'athlete-dashboard'),
                    array(
                        'a' => array(
                            'href' => array(),
                        ),
                    )
                ),
                esc_url(wp_login_url(get_permalink()))
            );
            ?>
        </p>
        <?php
    }
}

function athlete_dashboard_render_progress_section($title, $chart_id, $form_id, $nonce_name, $weight_field_name, $weight_unit_field_name) {
    ?>
    <div class="progress-section">
        <div class="progress-cards">
            <div class="progress-card">
                <h3><?php echo esc_html($title . ' ' . __('Progress', 'athlete-dashboard')); ?></h3>
                <div class="progress-chart-container">
                    <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
                </div>
            </div>
            <div class="progress-card">
                <h3><?php esc_html_e('Add New Entry', 'athlete-dashboard'); ?></h3>
                <form id="<?php echo esc_attr($form_id); ?>" class="progress-input-form custom-form">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($weight_field_name); ?>"><?php echo esc_html($title); ?> <?php esc_html_e('Weight:', 'athlete-dashboard'); ?></label>
                        <div class="weight-input-group">
                            <input type="number" id="<?php echo esc_attr($weight_field_name); ?>" name="<?php echo esc_attr($weight_field_name); ?>" required step="0.1">
                            <select id="<?php echo esc_attr($weight_unit_field_name); ?>" name="<?php echo esc_attr($weight_unit_field_name); ?>">
                                <option value="kg"><?php esc_html_e('kg', 'athlete-dashboard'); ?></option>
                                <option value="lbs"><?php esc_html_e('lbs', 'athlete-dashboard'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($form_id); ?>_date"><?php esc_html_e('Date:', 'athlete-dashboard'); ?></label>
                        <input type="date" id="<?php echo esc_attr($form_id); ?>_date" name="date" required>
                    </div>
                    <button type="submit" class="custom-button"><?php esc_html_e('Add Progress', 'athlete-dashboard'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php wp_nonce_field('athlete_dashboard_nonce', $nonce_name); ?>
    <?php
}