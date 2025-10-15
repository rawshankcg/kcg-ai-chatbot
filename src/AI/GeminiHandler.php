<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Gemini_Handler {
    
    private $api_key;
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $embedding_model = 'gemini-embedding-001';
    private $chat_model = 'gemini-2.5-pro';
    
    public function __construct() {
        $this->api_key = get_option('kcg_ai_chatbot_api_key', '');
    }
    
    /**
     * Generate embeddings for text using Gemini
     */
    public function generate_embedding($text) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini API key is not configured');
        }
        
        $url = $this->api_endpoint . $this->embedding_model . ':embedContent';
        
        $body = [
            'model' => 'models/' . $this->embedding_model,
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $this->api_key,
            ],
            'body'    => json_encode($body),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['embedding']['values'])) {
            return $data['embedding']['values'];
        }
        
        return new WP_Error('embedding_failed', 'Failed to generate embedding');
    }
    
    /**
     * Generate chat response using Gemini
     */
    public function generate_response($prompt, $context = '', $conversation_history = []) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini API key is not configured');
        }
        
        $model = get_option('kcg_ai_chatbot_model', 'gemini-2.5-pro');
        $url = $this->api_endpoint . $model . ':generateContent';
        
        $system_prompt = $this->build_system_prompt($context);
        
        $parts = [];
        
        $parts[] = ['text' => $system_prompt];
        
        foreach ($conversation_history as $message) {
            if (isset($message['user'])) {
                $parts[] = ['text' => "User: " . $message['user']];
            }
            if (isset($message['assistant'])) {
                $parts[] = ['text' => "Assistant: " . $message['assistant']];
            }
        }
        
        $parts[] = ['text' => "User: " . $prompt];
        
        $body = [
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'temperature'      => floatval(get_option('kcg_ai_chatbot_temperature', 0.7)),
                'maxOutputTokens'  => intval(get_option('kcg_ai_chatbot_max_tokens', 500)),
                'topP'             => 0.95,
                'topK'             => 40,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ]
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $this->api_key,
            ],
            'body'    => json_encode($body),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message'] ?? 'API Error occurred');
        }
        
        return new WP_Error('response_failed', 'Failed to generate response');
    }
    
    /**
     * Build system prompt with context
     */
    private function build_system_prompt($context = '') {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $custom_instructions = get_option('kcg_ai_chatbot_instructions', '');
        
        $prompt = "You are a helpful AI assistant for {$site_name}. ";
        
        if (!empty($site_description)) {
            $prompt .= "The website is about: {$site_description}. ";
        }
        
        if (!empty($custom_instructions)) {
            $prompt .= $custom_instructions . " ";
        }
        
        if (!empty($context)) {
            $prompt .= "\n\nUse the following information from our website to answer the user's question accurately:\n\n";
            $prompt .= $context;
            $prompt .= "\n\nIf the information doesn't contain the answer, you can provide general help based on your knowledge.";
        }
        
        $prompt .= "\n\nAlways be helpful, friendly, and professional. Keep responses concise but informative.";
        
        return $prompt;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $result = $this->generate_response("Hello, this is a test. Please respond with 'Connection successful!'");
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
}
