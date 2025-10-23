<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Chatbot_Design_Manager {
    
    /**
     * Initialize the design manager
     */
    public static function init() {
        // Add AJAX handler to update CSS
        add_action('wp_ajax_kcg_update_chatbot_css', array(__CLASS__, 'handle_css_update'));
    }
    
    /**
     * AJAX handler for updating CSS file with color customizations
     */
    public static function handle_css_update() {
        // Verify nonce and capabilities
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'kcg_ai_chatbot_design_action') || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $result = self::update_css_file();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('CSS updated successfully');
        }
        
        wp_die();
    }
    
    /**
     * Update the custom CSS file with color settings
     */
    public static function update_css_file() {
        // Get the source CSS file path
        $source_css_path = KCG_AI_CHATBOT_PLUGIN_DIR . 'assets/css/chat-widget.css';
        $custom_css_path = KCG_AI_CHATBOT_PLUGIN_DIR . 'assets/css/kcg-ai-chatbot-custom-colors.css';
        
        if (!file_exists($source_css_path)) {
            return new WP_Error('css_not_found', 'Source CSS file not found.');
        }
        
        // Get color options
        $header_bg = get_option('kcg_ai_chatbot_header_bg_color', '#667eea');
        $header_text = get_option('kcg_ai_chatbot_header_text_color', '#ffffff');
        $user_msg_bg = get_option('kcg_ai_chatbot_user_msg_bg_color', '#667eea');
        $user_msg_text = get_option('kcg_ai_chatbot_user_msg_text_color', '#ffffff');
        $bot_msg_bg = get_option('kcg_ai_chatbot_bot_msg_bg_color', '#ffffff');
        $bot_msg_text = get_option('kcg_ai_chatbot_bot_msg_text_color', '#1f2937');
        $button_bg = get_option('kcg_ai_chatbot_button_bg_color', '#667eea');
        $button_text = get_option('kcg_ai_chatbot_button_text_color', '#ffffff');
        
        // Build custom CSS
        $custom_css = "/* KCG AI Chatbot - Custom Colors */\n\n";
        
        // Header
        $custom_css .= ".kcg-chatbot-header {\n";
        $custom_css .= "    background: " . esc_attr($header_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($header_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        $custom_css .= ".kcg-chatbot-title h3 {\n";
        $custom_css .= "    color: " . esc_attr($header_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        // Bot avatar - matches bot message colors
        $custom_css .= ".kcg-bot-message .kcg-message-avatar {\n";
        $custom_css .= "    background: " . esc_attr($bot_msg_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($bot_msg_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        // User avatar - matches user message colors
        $custom_css .= ".kcg-user-message .kcg-message-avatar {\n";
        $custom_css .= "    background: " . esc_attr($user_msg_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($user_msg_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        // Buttons - both use the same button colors
        $custom_css .= ".kcg-chatbot-button {\n";
        $custom_css .= "    background: " . esc_attr($button_bg) . " !important;\n";
        $custom_css .= "}\n\n";
        
        $custom_css .= ".kcg-chatbot-button svg {\n";
        $custom_css .= "    color: " . esc_attr($button_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        $custom_css .= ".kcg-chatbot-send-btn {\n";
        $custom_css .= "    background: " . esc_attr($button_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($button_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        // User Messages
        $custom_css .= ".kcg-user-message .kcg-message-bubble {\n";
        $custom_css .= "    background: " . esc_attr($user_msg_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($user_msg_text) . " !important;\n";
        $custom_css .= "}\n\n";
        
        // Bot Messages
        $custom_css .= ".kcg-bot-message .kcg-message-bubble {\n";
        $custom_css .= "    background: " . esc_attr($bot_msg_bg) . " !important;\n";
        $custom_css .= "    color: " . esc_attr($bot_msg_text) . " !important;\n";
        $custom_css .= "}\n";
        
        // Write the custom CSS file
        $write_result = file_put_contents($custom_css_path, $custom_css);
        
        if ($write_result === false) {
            return new WP_Error('write_failed', 'Failed to write custom CSS file.');
        }
        
        // Update version
        update_option('kcg_ai_chatbot_css_version', time());
        
        return true;
    }
}