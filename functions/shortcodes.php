<?php
// functions/shortcodes.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Shortcode to display user's email
function display_user_email() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        return esc_html($current_user->user_email);
    } else {
        return 'Not logged in.';
    }
}
add_shortcode('user_email', 'display_user_email');

// Shortcode to display user's username
function display_user_name() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        return esc_html($current_user->user_login);
    } else {
        return 'Not logged in.';
    }
}
add_shortcode('user_name', 'display_user_name');

function display_user_workouts() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your workouts.';
    }
    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'workout',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        )
    );
    $workouts = new WP_Query($args);
    if ($workouts->have_posts()) {
        $output = '<div class="user-workouts">';
        while ($workouts->have_posts()) {
            $workouts->the_post();
            $output .= '<div class="workout-entry">';
            $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
            $output .= '<div>' . wp_kses_post(get_the_excerpt()) . '</div>'; // Changed from get_the_content() to get_the_excerpt()
            $output .= '<button class="open-workout" data-workout-id="' . get_the_ID() . '">Open Workout</button>'; // Added Open Workout button
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Your workout is being reviewed by a certified personal trainer and will be added soon, email trainers@aiworkoutgenerator.com with questions.';
    }
}
add_shortcode('user_workouts', 'display_user_workouts');

// Shortcode to display user's weight progress
function display_user_progress() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your progress.';
    }

    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'progress',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        )
    );
    $progress = new WP_Query($args);

    if ($progress->have_posts()) {
        $progress_data = array();
        while ($progress->have_posts()) {
            $progress->the_post();
            $progress_data[] = array(
                'date' => get_the_date('Y-m-d'),
                'weight' => get_post_meta(get_the_ID(), 'weight', true),
                'unit' => get_post_meta(get_the_ID(), 'unit', true)
            );
        }
        wp_reset_postdata();
        return '<div id="user-progress-data" style="display:none;" data-progress="' . esc_attr(json_encode($progress_data)) . '"></div>';
    } else {
        return '<div id="user-progress-data" style="display:none;" data-progress="[]"></div>';
    }
}
add_shortcode('user_progress', 'display_user_progress');

// Shortcode to display user's squat progress
function display_user_squat_progress() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your squat progress.';
    }

    $current_user = wp_get_current_user();
    $progress = get_user_meta($current_user->ID, 'squat_progress', true);

    if (!empty($progress) && is_array($progress)) {
        return '<div id="user-squat-progress-data" style="display:none;" data-progress="' . esc_attr(json_encode($progress)) . '"></div>';
    } else {
        return '<div id="user-squat-progress-data" style="display:none;" data-progress="[]"></div>';
    }
}
add_shortcode('user_squat_progress', 'display_user_squat_progress');

// Shortcode to display user's bench press progress
function display_user_bench_press_progress() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your bench press progress.';
    }

    $current_user = wp_get_current_user();
    $progress = get_user_meta($current_user->ID, 'bench_press_progress', true);

    if (!empty($progress) && is_array($progress)) {
        return '<div id="user-bench-press-progress-data" style="display:none;" data-progress="' . esc_attr(json_encode($progress)) . '"></div>';
    } else {
        return '<div id="user-bench-press-progress-data" style="display:none;" data-progress="[]"></div>';
    }
}
add_shortcode('user_bench_press_progress', 'display_user_bench_press_progress');

/**
 * Shortcode to display user's deadlift progress
 */
function display_user_deadlift_progress() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your deadlift progress.';
    }

    $current_user = wp_get_current_user();
    $progress = get_user_meta($current_user->ID, 'deadlift_progress', true);

    if (!empty($progress) && is_array($progress)) {
        return '<div id="user-deadlift-progress-data" style="display:none;" data-progress="' . esc_attr(json_encode($progress)) . '"></div>';
    } else {
        return '<div id="user-deadlift-progress-data" style="display:none;" data-progress="[]"></div>';
    }
}
add_shortcode('user_deadlift_progress', 'display_user_deadlift_progress');

// Shortcode to display user's overview
function display_user_overview() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your overview.';
    }

    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'overview',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        )
    );
    $overview = new WP_Query($args);

    if ($overview->have_posts()) {
        $overview->the_post();
        $output = '<div class="user-overview">';
        $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
        $output .= '<div>' . wp_kses_post(get_the_content()) . '</div>';
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Your trainer is being assigned and your overview will be added shortly.';
    }
}
add_shortcode('user_overview', 'display_user_overview');

// Shortcode to display user's fitness plan
function display_user_fitness_plan() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your fitness plan.';
    }

    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'fitness_plan',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        )
    );
    $fitness_plan = new WP_Query($args);

    if ($fitness_plan->have_posts()) {
        $fitness_plan->the_post();
        $output = '<div class="user-fitness-plan">';
        $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
        $output .= '<div>' . wp_kses_post(get_the_content()) . '</div>';
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Your assigned trainer is reviewing your workout and will be addin a plan shortly.';
    }
}
add_shortcode('user_fitness_plan', 'display_user_fitness_plan');

// Shortcode to display user's nutrition
function display_user_nutrition() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your nutrition plan.';
    }

    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'nutrition',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        )
    );
    $nutrition = new WP_Query($args);

    if ($nutrition->have_posts()) {
        $output = '<div class="user-nutrition">';
        while ($nutrition->have_posts()) {
            $nutrition->the_post();
            $output .= '<div class="nutrition-entry">';
            $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
            $output .= '<div>' . wp_kses_post(get_the_content()) . '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Please subscribe to the appropriate membership to receive a personalized nutrition program.';
    }
}
add_shortcode('user_nutrition', 'display_user_nutrition');
// Shortcode to display user's upcoming workouts
function display_user_upcoming_workouts() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your upcoming workouts.';
    }

    $current_user = wp_get_current_user();
    $args = array(
        'post_type' => 'upcoming_workouts',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'assigned_subscriber',
                'value' => $current_user->ID,
                'compare' => '='
            )
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'workout_date',
        'order' => 'ASC'
    );
    $upcoming_workouts = new WP_Query($args);

    if ($upcoming_workouts->have_posts()) {
        $output = '<div class="user-upcoming-workouts">';
        while ($upcoming_workouts->have_posts()) {
            $upcoming_workouts->the_post();
            $output .= '<div class="upcoming-workout">';
            $output .= '<h3>' . esc_html(get_the_title()) . '</h3>';
            $output .= '<div>' . wp_kses_post(get_the_content()) . '</div>';
            $workout_date = get_post_meta(get_the_ID(), 'workout_date', true);
            if ($workout_date) {
                $output .= '<p>Date: ' . esc_html(date('F j, Y', strtotime($workout_date))) . '</p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Subscribe to receive a workout plan with progressions and unlimited workouts.';
    }
}
add_shortcode('user_upcoming_workouts', 'display_user_upcoming_workouts');

/**
 * Shortcode to display user's logged workouts
 */
function display_user_logged_workouts($atts) {
    $atts = shortcode_atts(array(
        'limit' => 5
    ), $atts, 'user_logged_workouts');

    if (!is_user_logged_in()) {
        return 'Please log in to view your logged workouts.';
    }

    $current_user = wp_get_current_user();
    $workout_logs = athlete_dashboard_get_user_workout_logs($current_user->ID, array('posts_per_page' => $atts['limit']));

    if (empty($workout_logs)) {
        return 'No logged workouts found.';
    }

    $output = '<div class="user-logged-workouts">';
    foreach ($workout_logs as $log) {
        $output .= '<div class="workout-log-entry">';
        $output .= '<h4>' . esc_html($log['date']) . ' - ' . esc_html($log['type']) . '</h4>';
        $output .= '<p>Duration: ' . esc_html($log['duration']) . ' minutes</p>';
        $output .= '<p>Intensity: ' . esc_html($log['intensity']) . '/10</p>';
        if (!empty($log['notes'])) {
            $output .= '<p>Notes: ' . esc_html($log['notes']) . '</p>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('user_logged_workouts', 'display_user_logged_workouts');

/**
 * Shortcode to display user's logged meals
 */
function display_user_logged_meals($atts) {
    $atts = shortcode_atts(array(
        'limit' => 5
    ), $atts, 'user_logged_meals');

    if (!is_user_logged_in()) {
        return 'Please log in to view your logged meals.';
    }

    $current_user = wp_get_current_user();
    $meal_logs = athlete_dashboard_get_user_meal_logs($current_user->ID, array('posts_per_page' => $atts['limit']));

    if (empty($meal_logs)) {
        return 'No logged meals found.';
    }

    $output = '<div class="user-logged-meals">';
    foreach ($meal_logs as $log) {
        $output .= '<div class="meal-log-entry">';
        $output .= '<h4>' . esc_html($log['date']) . ' - ' . esc_html($log['type']) . ': ' . esc_html($log['name']) . '</h4>';
        
        // Protein
        if (!empty($log['protein']['type'])) {
            $output .= '<p><strong>Protein:</strong> ' . esc_html($log['protein']['type']) . ' - ' . 
                       esc_html($log['protein']['quantity']) . ' ' . esc_html($log['protein']['unit']) . '</p>';
        }
        
        // Fat
        if (!empty($log['fat']['type'])) {
            $output .= '<p><strong>Fat:</strong> ' . esc_html($log['fat']['type']) . ' - ' . 
                       esc_html($log['fat']['quantity']) . ' ' . esc_html($log['fat']['unit']) . '</p>';
        }
        
        // Carbohydrates: Starches & Grains
        if (!empty($log['carb_starch']['type'])) {
            $output .= '<p><strong>Starch/Grain:</strong> ' . esc_html($log['carb_starch']['type']) . ' - ' . 
                       esc_html($log['carb_starch']['quantity']) . ' ' . esc_html($log['carb_starch']['unit']) . '</p>';
        }
        
        // Carbohydrates: Fruits
        if (!empty($log['carb_fruit']['type'])) {
            $output .= '<p><strong>Fruit:</strong> ' . esc_html($log['carb_fruit']['type']) . ' - ' . 
                       esc_html($log['carb_fruit']['quantity']) . ' ' . esc_html($log['carb_fruit']['unit']) . '</p>';
        }
        
        // Carbohydrates: Vegetables
        if (!empty($log['carb_vegetable']['type'])) {
            $output .= '<p><strong>Vegetable:</strong> ' . esc_html($log['carb_vegetable']['type']) . ' - ' . 
                       esc_html($log['carb_vegetable']['quantity']) . ' ' . esc_html($log['carb_vegetable']['unit']) . '</p>';
        }
        
        $output .= '<p><strong>Calories:</strong> ' . esc_html($log['calories']) . '</p>';
        
        if (!empty($log['description'])) {
            $output .= '<p><strong>Description:</strong> ' . esc_html($log['description']) . '</p>';
        }
        
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('user_logged_meals', 'display_user_logged_meals');

/**
 * Shortcode to display the messaging interface
 */
function athlete_dashboard_messaging_shortcode() {
    if (!is_user_logged_in()) {
        return 'Please log in to view your messages.';
    }

    ob_start();
    ?>
    <div class="athlete-messaging-container">
        <div class="conversation-list">
            <h3><?php esc_html_e('Conversations', 'athlete-dashboard'); ?></h3>
            <ul id="conversation-list-items">
                <!-- Conversations will be loaded here dynamically -->
            </ul>
        </div>
        <div class="message-area">
            <div id="message-container">
                <!-- Messages will be loaded here dynamically -->
            </div>
            <div class="message-input-area">
                <input type="text" id="message-input" placeholder="<?php esc_attr_e('Type your message...', 'athlete-dashboard'); ?>">
                <button id="send-message"><?php esc_html_e('Send', 'athlete-dashboard'); ?></button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('athlete_messaging', 'athlete_dashboard_messaging_shortcode');

/**
 * Shortcode to display recent messages preview
 */
function athlete_dashboard_recent_messages_shortcode($atts) {
    $atts = shortcode_atts(array(
        'user_id' => get_current_user_id(),
    ), $atts, 'athlete_recent_messages');

    $user_id = intval($atts['user_id']);
    $conversations = athlete_dashboard_get_conversations($user_id);

    ob_start();
    ?>
    <div class="athlete-recent-messages">
        <h3><?php esc_html_e('Recent Messages', 'athlete-dashboard'); ?></h3>
        <?php if (!empty($conversations)) : ?>
            <ul class="recent-messages-list">
                <?php foreach ($conversations as $conversation) : 
                    $latest_message = athlete_dashboard_get_latest_message($conversation->id);
                    if ($latest_message) :
                ?>
                    <li class="message-preview">
                        <strong><?php echo esc_html($conversation->name); ?>:</strong>
                        <?php echo wp_trim_words($latest_message->message_content, 10); ?>
                        <span class="message-time">(<?php echo esc_html(human_time_diff(strtotime($latest_message->created_at), current_time('timestamp'))); ?> ago)</span>
                    </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e('No recent messages.', 'athlete-dashboard'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('athlete_recent_messages', 'athlete_dashboard_recent_messages_shortcode');