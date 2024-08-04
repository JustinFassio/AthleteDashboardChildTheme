<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function athlete_dashboard_create_messaging_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $conversations_table = $wpdb->prefix . 'ad_conversations';
    $messages_table = $wpdb->prefix . 'ad_messages';

    $sql = "CREATE TABLE $conversations_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        author_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY author_id (author_id)
    ) $charset_collate;

    CREATE TABLE $messages_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        conversation_id bigint(20) NOT NULL,
        sender_id bigint(20) NOT NULL,
        message_content text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY conversation_id (conversation_id),
        KEY sender_id (sender_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function athlete_dashboard_install() {
    athlete_dashboard_create_messaging_tables();
}

register_activation_hook(__FILE__, 'athlete_dashboard_install');