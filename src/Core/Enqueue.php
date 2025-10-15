<?php 
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Chatbot_Enqueue {
    public static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'kcg-ai-chatbot') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'kcg-ai-chatbot-admin',
            KCG_AI_CHATBOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            KCG_AI_CHATBOT_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'kcg-ai-chatbot-admin',
            KCG_AI_CHATBOT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            KCG_AI_CHATBOT_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('kcg-ai-chatbot-admin', 'kcgAiChatbotAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kcg_ai_chatbot_admin_nonce'),
            // ADDED: A specific nonce for the single post processing action
            'processSingleNonce' => wp_create_nonce('kcg_process_single'), 
            'strings' => array(
                'saved' => __('Settings saved successfully!', 'kaichat'),
                'error' => __('An error occurred. Please try again.', 'kaichat'),
                'testing' => __('Testing connection...', 'kaichat'),
                'success' => __('Connection successful!', 'kaichat'),
                'failed' => __('Connection failed!', 'kaichat'),
                'processing' => __('Processing...', 'kaichat'),
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Check if chatbot is enabled
        $enabled = get_option('kcg_ai_chatbot_enabled', true);
        if (!$enabled) {
            return;
        }
        
        // Frontend CSS
        wp_enqueue_style(
            'kcg-ai-chatbot-widget',
            KCG_AI_CHATBOT_PLUGIN_URL . 'assets/css/chat-widget.css',
            array(),
            KCG_AI_CHATBOT_VERSION
        );
        
        // Frontend JS
        wp_enqueue_script(
            'kcg-ai-chatbot-widget',
            KCG_AI_CHATBOT_PLUGIN_URL . 'assets/js/chat-widget.js',
            array('jquery'),
            KCG_AI_CHATBOT_VERSION,
            true
        );
        
        // Localize frontend script
        wp_localize_script('kcg-ai-chatbot-widget', 'kcgAiChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('kcg-ai-chatbot/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'welcomeMessage' => get_option('kcg_ai_chatbot_welcome_message', __('Hello! How can I help you?', 'kaichat')),
            'placeholder' => __('Type your message...', 'kaichat'),
            'strings' => array(
                'sendButton' => __('Send', 'kaichat'),
                'typing' => __('AI is typing...', 'kaichat'),
                'error' => __('Sorry, something went wrong. Please try again.', 'kaichat'),
                'networkError' => __('Network error. Please check your connection.', 'kaichat'),
                'online' => __('Online', 'kaichat'),
                'offline' => __('Offline', 'kaichat'),
            )
        ));
    }
}

function KCG_AI_Chatbot_Enqueue() {
    return KCG_AI_Chatbot_Enqueue::get_instance();
}

KCG_AI_Chatbot_Enqueue();