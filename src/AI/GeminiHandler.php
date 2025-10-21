<?php
/**
 * Complete GeminiHandler.php - Full Functional Code
 * File: src/AI/GeminiHandler.php
 * 
 * Features:
 * - Demo API key support with fallback
 * - Text embedding generation
 * - Chat response generation
 * - Conversation history support
 * - Context-aware responses
 * - Connection testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Gemini_Handler {
    
    private $api_key;
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $embedding_model = 'gemini-embedding-001';
    private $chat_model = 'gemini-2.5-pro';
    
    /**
     * Demo API key - Replace with your actual Google Gemini API key
     * Get yours from: https://makersuite.google.com/app/apikey
     * IMPORTANT: Use the SAME key as in ChatHandler.php
     */
    private $demo_api_key = 'AIzaSyCw7ppazznLnCblZ6p7nO4uOQK0lp2jjzU';
    
    public function __construct() {
        $saved_key = get_option('kcg_ai_chatbot_api_key', '');
        
        // Use saved key if exists, otherwise use demo key
        if (!empty($saved_key)) {
            $this->api_key = $saved_key;
        } else {
            $this->api_key = $this->demo_api_key;
        }
    }
    
    /**
     * Generate embeddings for text using Gemini
     * 
     * @param string $text Text to generate embedding for
     * @return array|WP_Error Embedding array or error
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
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message'] ?? 'API Error occurred');
        }
        
        return new WP_Error('embedding_failed', 'Failed to generate embedding');
    }
    
    /**
     * Generate chat response using Gemini
     * 
     * @param string $prompt User's message
     * @param string $context Context from knowledge base
     * @param array $conversation_history Previous conversation messages
     * @return array|WP_Error Response data or error
     */
    public function generate_response($prompt, $context = '', $conversation_history = []) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini API key is not configured');
        }
        
        $model = get_option('kcg_ai_chatbot_model', $this->chat_model);
        $url = $this->api_endpoint . $model . ':generateContent';
        
        // Build system prompt with context
        $system_prompt = $this->build_system_prompt($context);
        
        $parts = [];
        
        // Add system prompt
        $parts[] = ['text' => $system_prompt];
        
        // Add conversation history
        foreach ($conversation_history as $message) {
            if (isset($message['user'])) {
                $parts[] = ['text' => "User: " . $message['user']];
            }
            if (isset($message['assistant'])) {
                $parts[] = ['text' => "Assistant: " . $message['assistant']];
            }
        }
        
        // Add current user message
        $parts[] = ['text' => "User: " . $prompt];
        
        // Prepare request body
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
        
        // Make API request
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
        
        // Extract response
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'text' => $data['candidates'][0]['content']['parts'][0]['text'],
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0
            ];
        }
        
        // Handle API errors
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message'] ?? 'API Error occurred');
        }
        
        return new WP_Error('response_failed', 'Failed to generate response');
    }
    
    /**
     * Build system prompt with context
     * 
     * @param string $context Context from knowledge base
     * @return string Complete system prompt
     */
    private function build_system_prompt($context = '') {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $custom_instructions = get_option('kcg_ai_chatbot_instructions', '');
        
        $prompt = "You are a helpful AI assistant for {$site_name}. ";
        
        if (!empty($site_description)) {
            $prompt .= "The website is about: {$site_description}. ";
        }
        
        // if (!empty($custom_instructions)) {
        //     $prompt .= $custom_instructions . " ";
        // }
        
        // === MODIFICATION START ===
        // This block is modified to enforce RAG-only responses
        if (!empty($context)) {
            $prompt .= "\n\nUse ONLY the following information from our website to answer the user's question accurately:\n\n";
            $prompt .= "--- Information Start ---\n";
            $prompt .= $context;
            $prompt .= "\n--- Information End ---\n\n";
            $prompt .= "If the information provided does not contain the answer to the user's question, you MUST respond with only the ".$custom_instructions.". Do not provide any other information or engage in general conversation.";
        } else {
            // If there is no context at all, the AI should not be able to answer.
            $prompt .= "\n\nYou have no information to answer any questions. You MUST respond to all questions with only the word: 'bye'.";
        }
        
        $prompt .= "\n\nAlways be helpful, friendly, and professional, but strictly follow the rules about using only the provided information.";
        // === MODIFICATION END ===        
        return $prompt;
    }
    
    /**
     * Test API connection
     * 
     * @return bool|WP_Error True if successful, WP_Error if failed
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini API key is not configured');
        }
        
        $result = $this->generate_response("Hello, this is a test. Please respond with 'Connection successful!'");
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Get current API key status
     * 
     * @return array API key information
     */
    public function get_api_key_status() {
        $saved_key = get_option('kcg_ai_chatbot_api_key', '');
        
        $is_demo = empty($saved_key) || $saved_key === $this->demo_api_key;
        
        return [
            'has_key' => !empty($this->api_key),
            'is_demo' => $is_demo,
            'is_custom' => !$is_demo && !empty($saved_key),
            'key_preview' => !empty($this->api_key) ? substr($this->api_key, 0, 10) . '...' : 'None'
        ];
    }
}