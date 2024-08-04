<?php
/**
 * Admin Messaging Functionality for Athlete Dashboard
 *
 * This file contains functions to add messaging capabilities to the WordPress admin area.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add messaging section to user profile in admin area
 *
 * @param WP_User $user User object
 */
function athlete_dashboard_add_messaging_to_profile($user) {
    if (!current_user_can('edit_users') && !in_array('author', $user->roles)) {
        return;
    }
    ?>
    <h2><?php esc_html_e('Athlete Messages', 'athlete-dashboard'); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="athlete-messages"><?php esc_html_e('Recent Messages', 'athlete-dashboard'); ?></label></th>
            <td>
                <div id="athlete-messages">
                    <!-- Messages will be loaded here via AJAX -->
                </div>
                <button type="button" class="button" id="load-athlete-messages"><?php esc_html_e('Load Messages', 'athlete-dashboard'); ?></button>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        $('#load-athlete-messages').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_recent_athlete_messages',
                    user_id: <?php echo esc_js($user->ID); ?>,
                    nonce: '<?php echo wp_create_nonce('athlete_dashboard_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#athlete-messages').html(response.data);
                    } else {
                        alert('Error loading messages: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while loading messages.');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('show_user_profile', 'athlete_dashboard_add_messaging_to_profile');
add_action('edit_user_profile', 'athlete_dashboard_add_messaging_to_profile');

/**
 * AJAX handler for getting recent athlete messages
 */
function athlete_dashboard_get_recent_athlete_messages() {
    check_ajax_referer('athlete_dashboard_admin_nonce', 'nonce');
    if (!current_user_can('edit_users')) {
        wp_send_json_error('Insufficient permissions');
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
    }

    $conversations = athlete_dashboard_get_conversations($user_id);
    $output = '<ul>';
    foreach ($conversations as $conversation) {
        $messages = athlete_dashboard_get_messages($conversation->id);
        $last_message = end($messages);
        $output .= '<li>';
        $output .= '<strong>' . esc_html($conversation->name) . ':</strong> ';
        $output .= esc_html(wp_trim_words($last_message->message_content, 10));
        $output .= ' <a href="' . esc_url(admin_url('admin.php?page=athlete-messaging&conversation=' . $conversation->id)) . '">' . esc_html__('View Conversation', 'athlete-dashboard') . '</a>';
        $output .= '</li>';
    }
    $output .= '</ul>';

    wp_send_json_success($output);
}
add_action('wp_ajax_get_recent_athlete_messages', 'athlete_dashboard_get_recent_athlete_messages');

/**
 * Add messaging page to admin menu
 */
function athlete_dashboard_add_messaging_menu() {
    add_menu_page(
        __('Athlete Messaging', 'athlete-dashboard'),
        __('Athlete Messaging', 'athlete-dashboard'),
        'edit_users',
        'athlete-messaging',
        'athlete_dashboard_render_messaging_page',
        'dashicons-email',
        30
    );
}
add_action('admin_menu', 'athlete_dashboard_add_messaging_menu');

/**
 * Render the messaging page in admin area
 */
function athlete_dashboard_render_messaging_page() {
    // Check user capabilities
    if (!current_user_can('edit_users')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'athlete-dashboard'));
    }

    // Get the conversation ID from the URL, if any
    $conversation_id = isset($_GET['conversation']) ? intval($_GET['conversation']) : 0;

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($conversation_id) : ?>
            <?php athlete_dashboard_render_admin_conversation($conversation_id); ?>
        <?php else : ?>
            <?php athlete_dashboard_render_admin_conversation_list(); ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render the conversation list in admin area
 */
function athlete_dashboard_render_admin_conversation_list() {
    $conversations = athlete_dashboard_get_all_conversations();
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Athlete', 'athlete-dashboard'); ?></th>
                <th><?php esc_html_e('Last Message', 'athlete-dashboard'); ?></th>
                <th><?php esc_html_e('Actions', 'athlete-dashboard'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conversations as $conversation) : ?>
                <tr>
                    <td><?php echo esc_html($conversation->athlete_name); ?></td>
                    <td><?php echo esc_html(wp_trim_words($conversation->last_message, 10)); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=athlete-messaging&conversation=' . $conversation->id)); ?>" class="button">
                            <?php esc_html_e('View Conversation', 'athlete-dashboard'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render a single conversation in admin area
 *
 * @param int $conversation_id The ID of the conversation to render
 */
function athlete_dashboard_render_admin_conversation($conversation_id) {
    $messages = athlete_dashboard_get_messages($conversation_id);
    $conversation = athlete_dashboard_get_conversation($conversation_id);

    if (!$conversation) {
        wp_die(__('Conversation not found.', 'athlete-dashboard'));
    }

    ?>
    <h2><?php printf(esc_html__('Conversation with %s', 'athlete-dashboard'), esc_html($conversation->athlete_name)); ?></h2>
    
    <div id="conversation-messages">
        <?php foreach ($messages as $message) : ?>
            <div class="message <?php echo $message->sender_id === get_current_user_id() ? 'sent' : 'received'; ?>">
                <strong><?php echo esc_html($message->sender_name); ?>:</strong>
                <p><?php echo esc_html($message->message_content); ?></p>
                <span class="message-time"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->created_at))); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <form id="send-message-form" method="post">
        <?php wp_nonce_field('send_message_nonce', 'send_message_nonce'); ?>
        <input type="hidden" name="conversation_id" value="<?php echo esc_attr($conversation_id); ?>">
        <textarea name="message_content" required></textarea>
        <button type="submit" class="button button-primary"><?php esc_html_e('Send Message', 'athlete-dashboard'); ?></button>
    </form>

    <script>
    jQuery(document).ready(function($) {
        $('#send-message-form').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $(this).serialize() + '&action=send_admin_message',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error sending message: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while sending the message.');
                }
            });
        });
    });
    </script>
    <?php
}
