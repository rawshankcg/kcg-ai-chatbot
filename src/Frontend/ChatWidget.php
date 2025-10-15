<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Chatbot_Widget {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('wp_footer', array($this, 'render_chat_widget'));
    }
    
    public function render_chat_widget() {
        $enabled = get_option('kcg_ai_chatbot_enabled', true);
        
        if (!$enabled) {
            return;
        }

        if (file_exists(KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/frontend/chat-widget.php')) {
            include KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/frontend/chat-widget.php';
        }
    }
}

// Initialize the widget
function KCG_AI_Chatbot_Widget() {
    return KCG_AI_Chatbot_Widget::get_instance();
}

KCG_AI_Chatbot_Widget();