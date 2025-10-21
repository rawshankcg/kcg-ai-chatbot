<?php
/**
 * Complete ChatHandler.php - Full Functional Code
 * File: src/AI/ChatHandler.php
 * 
 * Features:
 * - Demo API key support
 * - Token tracking (10,000 limit for demo key)
 * - Unlimited usage with custom API key
 * - Automatic token reset when custom key added
 * - REST API endpoint for chat
 */

if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Rest_Endpoints {

    /**
     * Demo API key - Replace with your actual Google Gemini API key
     * Get yours from: https://makersuite.google.com/app/apikey
     */
    private $demo_api_key = 'AIzaSyCw7ppazznLnCblZ6p7nO4uOQK0lp2jjzU';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('kcg-ai-chatbot/v1', '/chat', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_chat_request'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle chat request from frontend
     */
    public function handle_chat_request(WP_REST_Request $request) {
        // Get request parameters
        $params = $request->get_json_params();
        $message = sanitize_textarea_field($params['message'] ?? '');
        $session_id = sanitize_text_field($params['session_id'] ?? '');

        // Validate message
        if (empty($message)) {
            return new WP_REST_Response(array(
                'success' => false, 
                'response' => 'Empty message received.'
            ), 400);
        }

        // Check token limit before processing
        $token_limit_check = $this->check_token_limit();
        if (is_wp_error($token_limit_check)) {
            return new WP_REST_Response(array(
                'success' => false, 
                'response' => $token_limit_check->get_error_message(),
                'token_limit_reached' => true
            ), 403);
        }

        // Initialize handlers
        $gemini_handler = new KCG_AI_Gemini_Handler();
        $vector_model = new KCG_AI_Vector_Model();
        
        // Build context from knowledge base
        $context = '';
        
        // Generate embedding for user query
        $query_embedding = $gemini_handler->generate_embedding($message);

        // Search for similar content in knowledge base
        if (!is_wp_error($query_embedding)) {
            $similar_chunks = $vector_model->search_similar($query_embedding, 3); 
            if (!empty($similar_chunks)) {
                foreach($similar_chunks as $chunk) {
                    $context .= $chunk->content . "\n\n";
                }
            }
        }

        // Generate AI response
        $response_data = $gemini_handler->generate_response($message, $context);

        // Handle errors
        if (is_wp_error($response_data)) {
            return new WP_REST_Response(array(
                'success' => false, 
                'response' => $response_data->get_error_message()
            ), 500);
        }

        // Extract response and token usage
        $response_text = is_array($response_data) ? $response_data['text'] : $response_data;
        $tokens_used = is_array($response_data) ? ($response_data['tokens_used'] ?? 0) : 0;

        // Update total token count (only in demo mode)
        $this->update_total_tokens($tokens_used);

        // Save conversation to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';
        $user_id = get_current_user_id(); 

        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'user_message' => $message,
                'bot_response' => $response_text,
                'tokens_used' => $tokens_used,
                'created_at' => current_time('mysql'),
            ),
            array(
                '%s', // session_id
                '%d', // user_id
                '%s', // user_message
                '%s', // bot_response
                '%d', // tokens_used
                '%s', // created_at
            )
        );

        // Get current total tokens for response
        $total_tokens = intval(get_option('kcg_ai_chatbot_total_tokens', 0));
        $remaining_tokens = 10000 - $total_tokens;

        // Return success response
        return new WP_REST_Response(array(
            'success' => true, 
            'response' => $response_text,
            'session_id' => $session_id,
            'tokens_used' => $tokens_used,
            'total_tokens' => $total_tokens,
            'remaining_tokens' => max(0, $remaining_tokens)
        ), 200);
    }

    /**
     * Check if token limit has been reached
     * Only applies when using demo API key
     * 
     * @return bool|WP_Error True if OK, WP_Error if limit reached
     */
    private function check_token_limit() {
        $api_key = get_option('kcg_ai_chatbot_api_key', '');
        $total_tokens = intval(get_option('kcg_ai_chatbot_total_tokens', 0));
        $token_limit = 10000;

        if (!empty($api_key) && $api_key !== $this->demo_api_key) {
            return true; 
        }

        if ($total_tokens >= $token_limit) {
            return new WP_Error(
                'token_limit_reached',
                sprintf(
                    __('You have reached the token limit of %s. Please add your own Google Gemini API key in the settings to continue using the chatbot.', 'kaichat'),
                    number_format($token_limit)
                )
            );
        }

        return true;
    }

    /**
     * Update the total token count
     * Only tracks tokens when using demo API key
     * 
     * @param int $tokens_used Number of tokens used in this request
     * @return int New total token count
     */
    private function update_total_tokens($tokens_used) {
        $api_key = get_option('kcg_ai_chatbot_api_key', '');
        
        // Only track tokens if using demo key or no key
        if (empty($api_key) || $api_key === $this->demo_api_key) {
            $total_tokens = intval(get_option('kcg_ai_chatbot_total_tokens', 0));
            $new_total = $total_tokens + $tokens_used;
            update_option('kcg_ai_chatbot_total_tokens', $new_total);
            return $new_total;
        }
        
        // If custom API key exists, don't increment counter
        return 0;
    }

    /**
     * Reset token count
     * Used when user adds custom API key
     * 
     * @return void
     */
    public static function reset_token_count() {
        update_option('kcg_ai_chatbot_total_tokens', 0);
        update_option('kcg_ai_chatbot_token_reset_date', current_time('mysql'));
    }

    /**
     * Check if using demo API key
     * 
     * @return bool True if using demo key, false if custom key
     */
    public function is_using_demo_key() {
        $api_key = get_option('kcg_ai_chatbot_api_key', '');
        
        // No key = demo mode
        if (empty($api_key)) {
            return true;
        }
        
        // Matches demo key = demo mode
        if ($api_key === $this->demo_api_key) {
            return true;
        }
        
        return false;
    }

    /**
     * Get token usage statistics
     * 
     * @return array Token usage data
     */
    public static function get_token_stats() {
        $total_tokens = intval(get_option('kcg_ai_chatbot_total_tokens', 0));
        $token_limit = 10000;
        $remaining_tokens = max(0, $token_limit - $total_tokens);
        $percentage = ($total_tokens / $token_limit) * 100;
        
        return array(
            'total_tokens' => $total_tokens,
            'token_limit' => $token_limit,
            'remaining_tokens' => $remaining_tokens,
            'percentage' => $percentage,
            'limit_reached' => $total_tokens >= $token_limit
        );
    }
}

// Initialize the REST endpoints
new KCG_AI_Rest_Endpoints();