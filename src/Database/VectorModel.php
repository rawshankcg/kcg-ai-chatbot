<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Vector_Model {
    
    private $table_name;
    
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
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE content_hash = %s",
            $content_hash
        ));
        
        if ($existing) {
            return $wpdb->update(
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
        } else {
            return $wpdb->insert(
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
        }
    }
    
    /**
     * Search vectors by similarity
     */
    public function search_similar($query_embedding, $limit = 5) {
        global $wpdb;
        
        $vectors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
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
        
        if ($source_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE source = %s AND source_id = %d",
                $source,
                $source_id
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE source = %s",
            $source
        ));
    }
    
    /**
     * Delete vectors by source
     */
    public function delete_by_source($source, $source_id = null) {
        global $wpdb;
        
        if ($source_id) {
            return $wpdb->delete(
                $this->table_name,
                ['source' => $source, 'source_id' => $source_id],
                ['%s', '%d']
            );
        }
        
        return $wpdb->delete(
            $this->table_name,
            ['source' => $source],
            ['%s']
        );
    }
}