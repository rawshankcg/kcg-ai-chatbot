<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Content_Processor {
    
    private $gemini_handler;
    private $vector_model;
    private $chunk_size = 500;
    
    public function __construct() {
        $this->gemini_handler = new KCG_AI_Gemini_Handler();
        $this->vector_model = new KCG_AI_Vector_Model();
    }
    
    /**
     * Process a WordPress post/page
     */
    public function process_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found');
        }
        
        $content = $this->prepare_post_content($post);
        
        $chunks = $this->split_into_chunks($content);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($chunks as $index => $chunk) {
            $metadata = [
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'post_url' => get_permalink($post->ID),
                'chunk_index' => $index,
                'total_chunks' => count($chunks),
                'post_date' => $post->post_date,
                'post_modified' => $post->post_modified,
            ];
            

            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy, ['fields' => 'names']);
                if (!empty($terms)) {
                    $metadata['taxonomy_' . $taxonomy] = implode(', ', $terms);
                }
            }
            
            $embedding = $this->gemini_handler->generate_embedding($chunk);
            
            if (is_wp_error($embedding)) {
                $error_count++;
                continue;
            }
            
            $result = $this->vector_model->insert_vector(
                $chunk,
                $embedding,
                $metadata,
                'post',
                $post->ID
            );
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        return [
            'success' => $success_count,
            'errors' => $error_count,
            'total' => count($chunks)
        ];
    }
    
    /**
     * Prepare post content for processing
     */
    private function prepare_post_content($post) {
        $content = '';
        
        $content .= $post->post_title . "\n\n";
        
        $content .= wp_strip_all_tags($post->post_content);
        
        if (!empty($post->post_excerpt)) {
            $content .= "\n\n" . $post->post_excerpt;
        }
        
        $custom_fields = get_post_meta($post->ID);
        foreach ($custom_fields as $key => $value) {
            if (substr($key, 0, 1) !== '_' && !is_array($value[0])) {
                $content .= "\n" . $key . ': ' . $value[0];
            }
        }
        
        return $content;
    }
    
    /**
     * Split content into chunks
     */
    private function split_into_chunks($content) {
        $words = explode(' ', $content);
        $chunks = [];
        
        for ($i = 0; $i < count($words); $i += $this->chunk_size) {
            $chunk = array_slice($words, $i, $this->chunk_size);
            $chunks[] = implode(' ', $chunk);
        }
        
        return $chunks;
    }
    
    /**
     * Process multiple posts
     */
    public function process_bulk_posts($post_ids) {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($post_ids as $post_id) {
            $result = $this->process_post($post_id);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['details'][$post_id] = $result->get_error_message();
            } else {
                $results['processed']++;
                $results['details'][$post_id] = $result;
            }
        }
        
        return $results;
    }
}