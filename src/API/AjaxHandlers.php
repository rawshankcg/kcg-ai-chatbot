<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Ajax_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_kcg_process_single_post', array($this, 'process_single_post'));
        add_action('wp_ajax_kcg_test_gemini_connection', array($this, 'test_gemini_connection'));
    }
    
    public function process_single_post() {
        check_ajax_referer('kcg_process_single', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $post_id = intval($_POST['post_id']);
        
        $processor = new KCG_AI_Content_Processor();
        $result = $processor->process_post($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function test_gemini_connection() {
        check_ajax_referer('kcg_ai_chatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $gemini = new KCG_AI_Gemini_Handler();
        $result = $gemini->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Connection successful!');
    }
}

new KCG_AI_Ajax_Handlers();
