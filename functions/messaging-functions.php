<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function athlete_dashboard_get_conversations($user_id) {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'ad_conversations';
    $users_table = $wpdb->base_prefix . 'users';

    $conversations = $wpdb->get_results($wpdb->prepare(
        "SELECT c.id, c.author_id, u.display_name as name
         FROM $conversations_table c
         JOIN $users_table u ON c.author_id = u.ID
         WHERE c.user_id = %d
         ORDER BY c.updated_at DESC",
        $user_id
    ));

    return $conversations;
}

function athlete_dashboard_get_messages($conversation_id) {
    global $wpdb;
    $messages_table = $wpdb->prefix . 'ad_messages';
    $users_table = $wpdb->base_prefix . 'users';

    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.id, m.sender_id, m.message_content, m.created_at, u.display_name as sender_username
         FROM $messages_table m
         JOIN $users_table u ON m.sender_id = u.ID
         WHERE m.conversation_id = %d
         ORDER BY m.created_at ASC",
        $conversation_id
    ));

    return $messages;
}

function athlete_dashboard_send_message_to_user($user_id, $sender_id, $message_content) {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'ad_conversations';
    $messages_table = $wpdb->prefix . 'ad_messages';

    // Start a transaction
    $wpdb->query('START TRANSACTION');

    // Check if a conversation already exists
    $conversation_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $conversations_table WHERE user_id = %d AND author_id = %d",
        $user_id, $sender_id
    ));

    if (!$conversation_id) {
        // Create a new conversation
        $insert_result = $wpdb->insert(
            $conversations_table,
            array(
                'user_id' => $user_id,
                'author_id' => $sender_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        if ($insert_result === false) {
            $wpdb->query('ROLLBACK');
            error_log("Failed to create conversation. SQL: " . $wpdb->last_query);
            error_log("Database error: " . $wpdb->last_error);
            return "Failed to create conversation: " . $wpdb->last_error;
        }

        $conversation_id = $wpdb->insert_id;

        if (!$conversation_id) {
            $wpdb->query('ROLLBACK');
            error_log("Failed to get last insert ID for conversation");
            return "Failed to get conversation ID after insertion";
        }
    }

    // Send the message
    $result = $wpdb->insert(
        $messages_table,
        array(
            'conversation_id' => $conversation_id,
            'sender_id' => $sender_id,
            'message_content' => $message_content,
            'created_at' => current_time('mysql')
        )
    );

    if ($result === false) {
        $wpdb->query('ROLLBACK');
        return "Failed to insert message: " . $wpdb->last_error;
    }

    // Update the conversation's updated_at timestamp
    $update_result = $wpdb->update(
        $conversations_table,
        array('updated_at' => current_time('mysql')),
        array('id' => $conversation_id)
    );

    if ($update_result === false) {
        $wpdb->query('ROLLBACK');
        return "Failed to update conversation timestamp: " . $wpdb->last_error;
    }

    $wpdb->query('COMMIT');
    return true;
}

/**
 * Send a welcome message to a new user from the admin.
 *
 * @param int $user_id The ID of the user to receive the welcome message.
 * @return bool True if the message was sent successfully, false otherwise.
 */
function athlete_dashboard_send_welcome_message($user_id) {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'ad_conversations';
    $messages_table = $wpdb->prefix . 'ad_messages';

    // Check if the user already has any conversations
    $existing_conversations = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $conversations_table WHERE user_id = %d",
        $user_id
    ));

    if ($existing_conversations > 0) {
        return false; // User already has conversations, no need to send welcome message
    }

    // Get the admin user (assuming ID 1 is the main admin)
    $admin_id = 1;

    // Start a transaction
    $wpdb->query('START TRANSACTION');

    // Create a new conversation
    $wpdb->insert(
        $conversations_table,
        array(
            'user_id' => $user_id,
            'author_id' => $admin_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        )
    );

    $conversation_id = $wpdb->insert_id;

    if (!$conversation_id) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    // Send the welcome message
    $welcome_message = "Welcome to the Athlete Dashboard! This is your messaging center. If you have any questions or need assistance, feel free to reply to this message.";
    
    $wpdb->insert(
        $messages_table,
        array(
            'conversation_id' => $conversation_id,
            'sender_id' => $admin_id,
            'message_content' => $welcome_message,
            'created_at' => current_time('mysql')
        )
    );

    if ($wpdb->last_error) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    $wpdb->query('COMMIT');
    return true;
}

/**
 * Check and send welcome message if needed when rendering the messaging preview.
 */
function athlete_dashboard_check_and_send_welcome_message() {
    $user_id = get_current_user_id();
    $welcome_message_sent = get_user_meta($user_id, 'welcome_message_sent', true);

    if (!$welcome_message_sent) {
        $sent = athlete_dashboard_send_welcome_message($user_id);
        if ($sent) {
            update_user_meta($user_id, 'welcome_message_sent', true);
        }
    }
}