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
     * Clear all plugin-related caches
     */
    private function clear_plugin_caches() {
        // Clear object cache for vectors
        wp_cache_delete('kcg_total_vectors', 'kcg_ai_chatbot');
        wp_cache_delete('kcg_total_posts_indexed', 'kcg_ai_chatbot');
        
        // Clear all indexed posts cache (they use MD5 hashes, so we need to clear the group)
        wp_cache_flush_group('kcg_ai_chatbot');
        
        // Clear WordPress general cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Process all posts of given post types
     */
    public function process_all_posts($post_types = ['post', 'page']) {
        $results = [
            'total_posts' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);
            
            $results['total_posts'] += count($posts);
            
            foreach ($posts as $post_id) {
                $result = $this->process_post($post_id);
                
                if (is_wp_error($result)) {
                    $results['failed']++;
                    $results['errors'][] = "Post ID {$post_id}: " . $result->get_error_message();
                } else {
                    $results['successful']++;
                }
            }
        }
        
        // Clear caches after bulk processing
        $this->clear_plugin_caches();
        
        return $results;
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
        
        // Clear caches after processing
        $this->clear_plugin_caches();
        
        if ($error_count > 0 && $success_count === 0) {
            return new WP_Error('processing_failed', 'Could not process any chunks for the post.');
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
        
        $content .= "Title: " . $post->post_title . "\n\n";
        
        $post_content = strip_shortcodes($post->post_content);
        $post_content = wp_strip_all_tags($post_content);
        $content .= $post_content;
        
        if (!empty($post->post_excerpt)) {
            $content .= "\n\nExcerpt: " . wp_strip_all_tags($post->post_excerpt);
        }
        
        $taxonomies = ['category', 'post_tag'];
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                $content .= "\n\n" . ucfirst($taxonomy) . "s: " . implode(', ', $term_names);
            }
        }
        
        return trim($content);
    }
    
    /**
     * Split content into chunks by words
     */
    private function split_into_chunks($content) {
        $words = preg_split('/\s+/', $content);
        $chunks = [];
        
        if (empty($words)) {
            return [];
        }
        
        $current_chunk = '';
        foreach($words as $word) {
            if (str_word_count($current_chunk . ' ' . $word) > $this->chunk_size) {
                $chunks[] = trim($current_chunk);
                $current_chunk = $word;
            } else {
                $current_chunk .= ' ' . $word;
            }
        }
        
        if (!empty($current_chunk)) {
            $chunks[] = trim($current_chunk);
        }
        
        return $chunks;
    }

    /**
     * Unindex a WordPress post/page
     */
    public function unindex_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found');
        }
        
        $deleted_count = $this->vector_model->delete_by_post($post_id);
        
        if ($deleted_count === false) {
            return new WP_Error('unindex_failed', 'Failed to remove vectors from database');
        }
        
        // Clear caches after unindexing
        $this->clear_plugin_caches();
        
        return [
            'success' => true,
            'deleted_vectors' => $deleted_count,
            'post_title' => $post->post_title
        ];
    }
}