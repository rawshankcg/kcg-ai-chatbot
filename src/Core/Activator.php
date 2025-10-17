<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class KCG_AI_Chatbot_Activator {
    
    public static function activate() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        self::create_conversations_table($wpdb, $charset_collate);
        self::create_vector_table($wpdb, $charset_collate);
        self::set_default_options();
    }
    
    private static function create_conversations_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_message text NOT NULL,
            bot_response text NOT NULL,
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function create_vector_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'kcg_ai_chatbot_vectors';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content text NOT NULL,
            content_hash varchar(64) NOT NULL,
            vector_embedding longtext NOT NULL,
            metadata longtext DEFAULT NULL,
            source varchar(255) DEFAULT NULL,
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            chunk_index int(11) DEFAULT 0,
            token_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY content_hash (content_hash),
            KEY source (source),
            KEY source_id (source_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    private static function set_default_options() {
        $defaults = [
            'kcg_ai_chatbot_enabled' => true,
            'kcg_ai_chatbot_api_key' => '',
            'kcg_ai_chatbot_model' => 'gpt-3.5-turbo',
            'kcg_ai_chatbot_max_tokens' => 500,
            'kcg_ai_chatbot_temperature' => 0.7,
            'kcg_ai_chatbot_welcome_message' => 'Hello! How can I help you today?',
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Design settings
        $design_defaults = [
            'kcg_ai_chatbot_header_bg_color' => '#667eea',
            'kcg_ai_chatbot_header_text_color' => '#ffffff',
            'kcg_ai_chatbot_user_msg_bg_color' => '#667eea',
            'kcg_ai_chatbot_user_msg_text_color' => '#ffffff',
            'kcg_ai_chatbot_bot_msg_bg_color' => '#ffffff',
            'kcg_ai_chatbot_bot_msg_text_color' => '#1f2937',
            'kcg_ai_chatbot_button_bg_color' => '#4F46E5',
            'kcg_ai_chatbot_button_text_color' => '#ffffff',
            'kcg_ai_chatbot_assistant_avatar' => '',
            'kcg_ai_chatbot_button_icon' => '',
            'kcg_ai_chatbot_design_updated' => time(),
            'kcg_ai_chatbot_css_version' => time()
        ];
        
        foreach ($design_defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}