// Ensure we're using strict mode
'use strict';

// Create a namespace for our messaging functionality
window.AthleteMessaging = (function($) {
    // Private variables
    let currentConversationId = null;

    // Private functions
    function loadConversations() {
        $.ajax({
            url: athleteDashboardMessaging.ajax_url,
            type: 'POST',
            data: {
                action: 'get_conversations',
                nonce: athleteDashboardMessaging.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    displayConversations(response.data);
                } else {
                    console.error('Error loading conversations:', response && response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading conversations:', error);
            }
        });
    }

    function displayConversations(conversations) {
        const $conversationList = $('#conversation-list-items');
        $conversationList.empty();
        if (Array.isArray(conversations)) {
            conversations.forEach(function(conversation) {
                $conversationList.append(`<li data-conversation-id="${conversation.id}">${conversation.name}</li>`);
            });
        } else {
            console.error('Conversations data is not an array:', conversations);
        }
    }

    function loadMessages(conversationId) {
        $.ajax({
            url: athleteDashboardMessaging.ajax_url,
            type: 'POST',
            data: {
                action: 'get_messages',
                nonce: athleteDashboardMessaging.nonce,
                conversation_id: conversationId
            },
            success: function(response) {
                if (response && response.success) {
                    displayMessages(response.data);
                } else {
                    console.error('Error loading messages:', response && response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading messages:', error);
            }
        });
    }

    function displayMessages(messages) {
        const $messageContainer = $('#message-container');
        $messageContainer.empty();
        if (Array.isArray(messages)) {
            messages.forEach(function(message) {
                const messageClass = message.sender_id == athleteDashboardMessaging.current_user_id ? 'user-message' : 'author-message';
                $messageContainer.append(`
                    <div class="message ${messageClass}">
                        <div class="username">${message.sender_name}</div>
                        ${message.message_content}
                    </div>
                `);
            });
            $messageContainer.scrollTop($messageContainer[0].scrollHeight);
        } else {
            console.error('Messages data is not an array:', messages);
        }
    }

    function sendMessage(messageContent) {
        if (!messageContent || !currentConversationId) return;

        $.ajax({
            url: athleteDashboardMessaging.ajax_url,
            type: 'POST',
            data: {
                action: 'send_message',
                nonce: athleteDashboardMessaging.nonce,
                conversation_id: currentConversationId,
                message: messageContent
            },
            success: function(response) {
                if (response && response.success) {
                    $('#message-input').val('');
                    loadMessages(currentConversationId);
                } else {
                    console.error('Error sending message:', response && response.data ? response.data.message : 'Unknown error');
                    alert('Error: ' + (response && response.data ? response.data.message : 'Unknown error occurred while sending the message.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error sending message:', error);
                alert('An error occurred while sending the message. Please try again.');
            }
        });
    }

    // Public functions
    function initialize() {
        loadConversations();

        // Event listeners
        $('#conversation-list-items').on('click', 'li', function() {
            currentConversationId = $(this).data('conversation-id');
            loadMessages(currentConversationId);
        });

        $('#send-message').on('click', function() {
            const messageContent = $('#message-input').val().trim();
            sendMessage(messageContent);
        });

        $('#message-input').on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                const messageContent = $(this).val().trim();
                sendMessage(messageContent);
            }
        });
    }

    // Admin messaging functionality
    function initializeAdminMessaging() {
        $('#send-athlete-message').on('click', function() {
            var content = $('#new-message-content').val();
            var userId = $(this).data('user-id');
            
            // Disable the button to prevent multiple clicks
            $(this).prop('disabled', true);
            
            $.ajax({
                url: athleteDashboardMessaging.ajax_url,
                type: 'POST',
                data: {
                    action: 'send_athlete_message',
                    nonce: athleteDashboardMessaging.admin_nonce,
                    user_id: userId,
                    message: content
                },
                success: function(response) {
                    if (response.success) {
                        alert('Message sent successfully!');
                        $('#new-message-content').val('');
                        // Refresh the messages
                        $('#athlete-messages').html(response.data.html);
                    } else {
                        console.error('Error sending message:', response.data.message);
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('An error occurred while sending the message. Please try again.');
                },
                complete: function() {
                    // Re-enable the button
                    $('#send-athlete-message').prop('disabled', false);
                }
            });
        });
    }

    // Return public methods
    return {
        initialize: initialize,
        initializeAdminMessaging: initializeAdminMessaging
    };
})(jQuery);

// Initialize messaging when the document is ready
jQuery(document).ready(function() {
    AthleteMessaging.initialize();
    if (typeof athleteDashboardMessaging !== 'undefined' && athleteDashboardMessaging.is_admin) {
        AthleteMessaging.initializeAdminMessaging();
    }
});