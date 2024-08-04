<?php
/**
 * Template Name: Athlete Messaging System
 *
 * @package CitronTheme
 */

get_header();

// Check if user is logged in
if ( !is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user = wp_get_current_user();
$is_administrator_or_author = in_array( 'administrator', $current_user->roles ) || in_array( 'author', $current_user->roles );

?>

<div class="athlete-dashboard-container">
    <?php athlete_dashboard_render_welcome_banner( $current_user ); ?>
    
    <div class="athlete-dashboard">
        <div id="messaging-system" class="dashboard-section full-width">
            <h2><?php esc_html_e( 'Messaging Center', 'citron-theme' ); ?></h2>
            <div class="messenger-container">
                <div class="conversation-list">
                    <h3><?php esc_html_e( 'Your Conversations', 'citron-theme' ); ?></h3>
                    <ul id="conversation-list-items">
                        <!-- Conversation list items will be dynamically populated here -->
                    </ul>
                </div>
                <div class="active-conversation">
                    <div id="message-container" class="message-container">
                        <!-- Messages will be dynamically populated here -->
                    </div>
                    <div class="input-area">
                        <input type="text" id="message-input" placeholder="<?php esc_attr_e( 'Type your message...', 'citron-theme' ); ?>">
                        <button id="send-message">➤</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>