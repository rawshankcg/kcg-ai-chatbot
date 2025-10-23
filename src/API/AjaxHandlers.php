<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Ajax_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_kcg_process_all_posts', array($this, 'process_all_posts'));
        add_action('wp_ajax_kcg_process_single_post', array($this, 'process_single_post'));
        add_action('wp_ajax_kcg_test_gemini_connection', array($this, 'test_gemini_connection'));
        add_action('wp_ajax_kcg_unindex_single_post', array($this, 'unindex_single_post'));
        add_action('wp_ajax_kcg_delete_session', array($this, 'delete_session'));
    }


    /**
     * Process All Post Types AJAX Handler
     */
    public function process_all_posts() {
        check_ajax_referer('kcg_process_all', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : ['post', 'page'];
        
        $processor = new KCG_AI_Content_Processor();
        $result = $processor->process_all_posts($post_types);
        
        if (!empty($result['errors'])) {
            wp_send_json_success([
                'message' => sprintf(
                    'Processing completed. %d successful, %d failed out of %d total posts.',
                    $result['successful'],
                    $result['failed'],
                    $result['total_posts']
                ),
                'details' => $result
            ]);
        } else {
            wp_send_json_success([
                'message' => sprintf(
                    'Successfully processed %d out of %d posts.',
                    $result['successful'],
                    $result['total_posts']
                ),
                'details' => $result
            ]);
        }
    }
    
    /**
     * Process Single Post AJAX Handler
     */
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

    public function unindex_single_post() {
        check_ajax_referer('kcg_unindex_single', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $post_id = intval($_POST['post_id']);
        
        $processor = new KCG_AI_Content_Processor();
        $result = $processor->unindex_post($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }

    /**
     * Delete Session AJAX Handler
     */
    public function delete_session() {
        check_ajax_referer('kcg_delete_session', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';
        
        $deleted = $wpdb->delete(
            $table_name,
            array('session_id' => $session_id),
            array('%s')
        );
        
        if ($deleted === false) {
            wp_send_json_error('Failed to delete session');
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: Number of messages that were deleted */
                __('Session deleted successfully. %d messages removed.', 'kcg-ai-chatbot'),
                $deleted
            ),
            'deleted_count' => $deleted
        ));
    }
}

new KCG_AI_Ajax_Handlers();