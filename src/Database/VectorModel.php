<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Vector_Model {
    
    private $table_name;
    private $cache_group = 'kcg_ai_chatbot';
    private $cache_expiration = 300; // 5 minutes
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kcg_ai_chatbot_vectors';
    }
    
    /**
     * Insert or update vector embedding
     */
    public function insert_vector($content, $embedding, $metadata = [], $source = '', $source_id = null) {
        global $wpdb;
        
        $content_hash = hash('sha256', $content);
        $token_count = str_word_count($content);
        
        // Check cache first
        $cache_key = 'vector_exists_' . $content_hash;
        $existing = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE content_hash = %s",
                $content_hash
            ));
            
            // Cache the result
            wp_cache_set($cache_key, $existing, $this->cache_group, $this->cache_expiration);
        }
        
        if ($existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with cache invalidation
            $result = $wpdb->update(
                $this->table_name,
                [
                    'vector_embedding' => json_encode($embedding),
                    'metadata' => json_encode($metadata),
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $existing],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
            // Invalidate related caches
            $this->invalidate_caches($source, $source_id);
            
            return $result;
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with cache invalidation
            $result = $wpdb->insert(
                $this->table_name,
                [
                    'content' => $content,
                    'content_hash' => $content_hash,
                    'vector_embedding' => json_encode($embedding),
                    'metadata' => json_encode($metadata),
                    'source' => $source,
                    'source_id' => $source_id,
                    'token_count' => $token_count,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
            );
            
            // Invalidate related caches
            $this->invalidate_caches($source, $source_id);
            
            return $result;
        }
    }
    
    /**
     * Search vectors by similarity
     */
    public function search_similar($query_embedding, $limit = 5) {
        global $wpdb;
        
        // Create cache key based on limit
        $cache_key = 'vectors_recent_' . $limit;
        $vectors = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $vectors) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
            $vectors = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            ));
            
            // Cache the results
            wp_cache_set($cache_key, $vectors, $this->cache_group, $this->cache_expiration);
        }
        
        $results = [];
        foreach ($vectors as $vector) {
            $stored_embedding = json_decode($vector->vector_embedding, true);
            $similarity = $this->cosine_similarity($query_embedding, $stored_embedding);
            
            $vector->similarity = $similarity;
            $results[] = $vector;
        }
        
        usort($results, function($a, $b) {
            return $b->similarity <=> $a->similarity;
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosine_similarity($vec1, $vec2) {
        $dot_product = 0;
        $norm_a = 0;
        $norm_b = 0;
        
        $count = min(count($vec1), count($vec2));
        
        for ($i = 0; $i < $count; $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $norm_a += $vec1[$i] * $vec1[$i];
            $norm_b += $vec2[$i] * $vec2[$i];
        }
        
        if ($norm_a == 0 || $norm_b == 0) {
            return 0;
        }
        
        return $dot_product / (sqrt($norm_a) * sqrt($norm_b));
    }
    
    /**
     * Get vectors by source
     */
    public function get_by_source($source, $source_id = null) {
        global $wpdb;
        
        // Create cache key
        $cache_key = $source_id 
            ? 'vectors_source_' . $source . '_' . $source_id 
            : 'vectors_source_' . $source;
        
        $results = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $results) {
            if ($source_id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE source = %s AND source_id = %d",
                    $source,
                    $source_id
                ));
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE source = %s",
                    $source
                ));
            }
            
            // Cache the results
            wp_cache_set($cache_key, $results, $this->cache_group, $this->cache_expiration);
        }
        
        return $results;
    }
    
    /**
     * Delete vectors by source
     */
    public function delete_by_source($source, $source_id = null) {
        global $wpdb;
        
        if ($source_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with cache invalidation
            $result = $wpdb->delete(
                $this->table_name,
                ['source' => $source, 'source_id' => $source_id],
                ['%s', '%d']
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with cache invalidation
            $result = $wpdb->delete(
                $this->table_name,
                ['source' => $source],
                ['%s']
            );
        }
        
        // Invalidate related caches
        $this->invalidate_caches($source, $source_id);
        
        return $result;
    }

    /**
     * Delete vectors by post ID
     */
    public function delete_by_post($post_id) {
        return $this->delete_by_source('post', $post_id);
    }

    /**
     * Get vector count by post ID
     */
    public function get_vector_count_by_post($post_id) {
        global $wpdb;
        
        // Create cache key
        $cache_key = 'vector_count_post_' . $post_id;
        $count = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE source = 'post' AND source_id = %d",
                $post_id
            ));
            
            // Cache the result
            wp_cache_set($cache_key, $count, $this->cache_group, $this->cache_expiration);
        }
        
        return $count;
    }
    
    /**
     * Invalidate all related caches when data changes
     */
    private function invalidate_caches($source = null, $source_id = null) {
        // Clear general caches
        wp_cache_delete('kcg_total_vectors', $this->cache_group);
        wp_cache_delete('kcg_total_posts_indexed', $this->cache_group);
        
        // Clear source-specific caches
        if ($source) {
            $cache_key = $source_id 
                ? 'vectors_source_' . $source . '_' . $source_id 
                : 'vectors_source_' . $source;
            
            wp_cache_delete($cache_key, $this->cache_group);
            
            // Clear count cache for posts
            if ($source === 'post' && $source_id) {
                wp_cache_delete('vector_count_post_' . $source_id, $this->cache_group);
            }
        }
        
        // Clear recent vectors cache (all limit variations)
        for ($i = 1; $i <= 20; $i++) {
            wp_cache_delete('vectors_recent_' . $i, $this->cache_group);
        }
        
        // Flush the entire cache group for thoroughness
        wp_cache_flush_group($this->cache_group);
    }
    
    /**
     * Get total vector count
     */
    public function get_total_count() {
        global $wpdb;
        
        $cache_key = 'kcg_total_vectors';
        $count = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
            
            // Cache the result
            wp_cache_set($cache_key, $count, $this->cache_group, $this->cache_expiration);
        }
        
        return $count;
    }
    
    /**
     * Get total indexed posts count
     */
    public function get_total_posts_indexed() {
        global $wpdb;
        
        $cache_key = 'kcg_total_posts_indexed';
        $count = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table with caching implementation, table name is sanitized in constructor
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT source_id) FROM {$this->table_name} WHERE source = %s",
                'post'
            ));
            
            // Cache the result
            wp_cache_set($cache_key, $count, $this->cache_group, $this->cache_expiration);
        }
        
        return $count;
    }
    
    /**
     * Clear all caches for this model
     */
    public function clear_all_caches() {
        $this->invalidate_caches();
    }
}