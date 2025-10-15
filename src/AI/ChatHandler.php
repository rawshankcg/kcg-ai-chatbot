<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Rest_Endpoints {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('kcg-ai-chatbot/v1', '/chat', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_chat_request'),
            'permission_callback' => '__return_true', // Publicly accessible
        ));
    }

    public function handle_chat_request(WP_REST_Request $request) {
        // Sanitize incoming data
        $params = $request->get_json_params();
        $message = sanitize_textarea_field($params['message'] ?? '');
        $session_id = sanitize_text_field($params['session_id'] ?? '');

        if (empty($message)) {
            return new WP_REST_Response(array('success' => false, 'response' => 'Empty message received.'), 400);
        }

        // Initialize dependencies
        $gemini_handler = new KCG_AI_Gemini_Handler();
        $vector_model = new KCG_AI_Vector_Model();
        
        $context = '';
        
        // 1. Generate an embedding for the user's query
        $query_embedding = $gemini_handler->generate_embedding($message);

        // 2. If embedding is successful, search for similar content in the knowledge base
        if (!is_wp_error($query_embedding)) {
            $similar_chunks = $vector_model->search_similar($query_embedding, 3); // Get top 3 chunks
            if (!empty($similar_chunks)) {
                foreach($similar_chunks as $chunk) {
                    $context .= $chunk->content . "\n\n";
                }
            }
        }

        // 3. Generate the final response from the AI, providing the context
        $response_text = $gemini_handler->generate_response($message, $context);

        if (is_wp_error($response_text)) {
            return new WP_REST_Response(array(
                'success' => false, 
                'response' => $response_text->get_error_message()
            ), 500);
        }

        // FIXED: Added the logic to save the conversation to the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';
        $user_id = get_current_user_id(); // Gets logged-in user ID, or 0 for guests

        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'user_message' => $message,
                'bot_response' => $response_text,
                'tokens_used' => 0, // Placeholder, as token count isn't returned by the current handler
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

        return new WP_REST_Response(array(
            'success' => true, 
            'response' => $response_text,
            'session_id' => $session_id
        ), 200);
    }
}

new KCG_AI_Rest_Endpoints();