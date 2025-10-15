<?php 
if (!defined('ABSPATH')) {
    exit;
}

$welcome_message = get_option('kcg_ai_chatbot_welcome_message', 'Hello! How can I help you today?');
?>

<!-- KCG AI Chatbot Widget -->
<div id="kcg-ai-chatbot-container" class="kcg-chatbot-hidden">
    
    <!-- Chat Button (Floating) -->
    <div id="kcg-chatbot-button" class="kcg-chatbot-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </div>
    
    <!-- Chat Window -->
    <div id="kcg-chatbot-window" class="kcg-chatbot-window" style="display: none;">
        
        <!-- Chat Header -->
        <div class="kcg-chatbot-header">
            <div class="kcg-chatbot-header-content">
                <div class="kcg-chatbot-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <div class="kcg-chatbot-title">
                    <h3><?php _e('AI Assistant', 'kaichat'); ?></h3>
                    <span class="kcg-chatbot-status">
                        <span class="kcg-status-dot"></span>
                        <?php _e('Online', 'kaichat'); ?>
                    </span>
                </div>
            </div>
            <button id="kcg-chatbot-close" class="kcg-chatbot-close" aria-label="<?php esc_attr_e('Close chat', 'kaichat'); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <!-- Chat Messages Area -->
        <div id="kcg-chatbot-messages" class="kcg-chatbot-messages">
            <!-- Welcome Message -->
            <div class="kcg-chat-message kcg-bot-message">
                <div class="kcg-message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <div class="kcg-message-content">
                    <div class="kcg-message-bubble">
                        <?php echo esc_html($welcome_message); ?>
                    </div>
                    <div class="kcg-message-time">
                        <?php echo current_time('g:i A'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Messages will be appended here by JavaScript -->
        </div>
        
        <!-- Typing Indicator (Hidden by default) -->
        <div id="kcg-chatbot-typing" class="kcg-typing-indicator" style="display: none;">
            <div class="kcg-typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="kcg-typing-text"><?php _e('AI is typing...', 'kaichat'); ?></span>
        </div>
        
        <!-- Chat Input Area -->
        <div class="kcg-chatbot-input-wrapper">
            <form id="kcg-chatbot-form" class="kcg-chatbot-form">
                <div class="kcg-input-container">
                    <textarea 
                        id="kcg-chatbot-input" 
                        class="kcg-chatbot-input" 
                        placeholder="<?php esc_attr_e('Type your message...', 'kaichat'); ?>"
                        rows="1"
                        maxlength="500"
                    ></textarea>
                    <button 
                        type="submit" 
                        id="kcg-chatbot-send" 
                        class="kcg-chatbot-send-btn"
                        aria-label="<?php esc_attr_e('Send message', 'kaichat'); ?>"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <div class="kcg-input-footer">
                    <span class="kcg-powered-by">
                        <?php _e('Powered by KCG AI', 'kaichat'); ?>
                    </span>
                </div>
            </form>
        </div>
        
    </div>
    
</div>

<!-- Hidden Session ID -->
<input type="hidden" id="kcg-chatbot-session-id" value="">

<!-- Message Templates (Hidden) -->
<template id="kcg-user-message-template">
    <div class="kcg-chat-message kcg-user-message">
        <div class="kcg-message-content">
            <div class="kcg-message-bubble"></div>
            <div class="kcg-message-time"></div>
        </div>
        <div class="kcg-message-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
    </div>
</template>

<template id="kcg-bot-message-template">
    <div class="kcg-chat-message kcg-bot-message">
        <div class="kcg-message-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
            </svg>
        </div>
        <div class="kcg-message-content">
            <div class="kcg-message-bubble"></div>
            <div class="kcg-message-time"></div>
        </div>
    </div>
</template>

<template id="kcg-error-message-template">
    <div class="kcg-chat-message kcg-error-message">
        <div class="kcg-message-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div class="kcg-message-content">
            <div class="kcg-message-bubble kcg-error-bubble"></div>
        </div>
    </div>
</template>