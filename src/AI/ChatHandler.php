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
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_chat_request(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $message = sanitize_textarea_field($params['message'] ?? '');
        $session_id = sanitize_text_field($params['session_id'] ?? '');

        if (empty($message)) {
            return new WP_REST_Response(array('success' => false, 'response' => 'Empty message received.'), 400);
        }

        $gemini_handler = new KCG_AI_Gemini_Handler();
        $vector_model = new KCG_AI_Vector_Model();
        
        $context = '';
        
        $query_embedding = $gemini_handler->generate_embedding($message);

        if (!is_wp_error($query_embedding)) {
            $similar_chunks = $vector_model->search_similar($query_embedding, 3); 
            if (!empty($similar_chunks)) {
                foreach($similar_chunks as $chunk) {
                    $context .= $chunk->content . "\n\n";
                }
            }
        }

        $response_data = $gemini_handler->generate_response($message, $context);

        if (is_wp_error($response_data)) {
            return new WP_REST_Response(array(
                'success' => false, 
                'response' => $response_data->get_error_message()
            ), 500);
        }

        $response_text = is_array($response_data) ? $response_data['text'] : $response_data;
        $tokens_used = is_array($response_data) ? ($response_data['tokens_used'] ?? 0) : 0;

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

        return new WP_REST_Response(array(
            'success' => true, 
            'response' => $response_text,
            'session_id' => $session_id
        ), 200);
    }
}

new KCG_AI_Rest_Endpoints();