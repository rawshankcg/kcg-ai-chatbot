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

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
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
            'processAllNonce' => wp_create_nonce('kcg_process_all'),
            'unindexSingleNonce' => wp_create_nonce('kcg_unindex_single'),
            'deleteSessionNonce' => wp_create_nonce('kcg_delete_session'),
            'strings' => array(
                'saved' => __('Settings saved successfully!', 'kcg-ai-chatbot'),
                'error' => __('An error occurred. Please try again.', 'kcg-ai-chatbot'),
                'testing' => __('Testing connection...', 'kcg-ai-chatbot'),
                'success' => __('Connection successful!', 'kcg-ai-chatbot'),
                'failed' => __('Connection failed!', 'kcg-ai-chatbot'),
                'processing' => __('Processing...', 'kcg-ai-chatbot'),
                'confirmBulk' => __('Are you sure you want to index ALL content? Once done, this cannot be undone.', 'kcg-ai-chatbot'),
                'notIndexed' => __('Not Indexed', 'kcg-ai-chatbot'),
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

        $custom_css_path = KCG_AI_CHATBOT_PLUGIN_DIR . 'assets/css/kcg-ai-chatbot-custom-colors.css';
        if (file_exists($custom_css_path)) {
            wp_enqueue_style(
                'kcg-ai-chatbot-custom-colors',
                KCG_AI_CHATBOT_PLUGIN_URL . 'assets/css/kcg-ai-chatbot-custom-colors.css',
                array('kcg-ai-chatbot-widget'),
                get_option('kcg_ai_chatbot_css_version', KCG_AI_CHATBOT_VERSION)
            );
        }
        
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
            'welcomeMessage' => get_option('kcg_ai_chatbot_welcome_message', __('Hello! How can I help you?', 'kcg-ai-chatbot')),
            'placeholder' => __('Type your message...', 'kcg-ai-chatbot'),
            'strings' => array(
                'sendButton' => __('Send', 'kcg-ai-chatbot'),
                'typing' => __('AI is typing...', 'kcg-ai-chatbot'),
                'error' => __('Sorry, something went wrong. Please try again.', 'kcg-ai-chatbot'),
                'networkError' => __('Network error. Please check your connection.', 'kcg-ai-chatbot'),
                'online' => __('Online', 'kcg-ai-chatbot'),
                'offline' => __('Offline', 'kcg-ai-chatbot'),
            )
        ));
    }
}

function KCG_AI_Chatbot_Enqueue() {
    return KCG_AI_Chatbot_Enqueue::get_instance();
}

KCG_AI_Chatbot_Enqueue();