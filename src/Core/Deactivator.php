<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Chatbot_Deactivator {
    
    public static function deactivate() {
        wp_clear_scheduled_hook('kcg_ai_chatbot_cleanup');
        
        flush_rewrite_rules();
    }
}