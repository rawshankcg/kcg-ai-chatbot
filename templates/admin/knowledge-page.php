<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$vector_table = $wpdb->prefix . 'kcg_ai_chatbot_vectors';

$cache_key_vectors = 'kcg_total_vectors';
$total_vectors = wp_cache_get($cache_key_vectors, 'kcg_ai_chatbot');

if (false === $total_vectors) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with caching
    $total_vectors = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($vector_table));
    wp_cache_set($cache_key_vectors, $total_vectors, 'kcg_ai_chatbot', 300); // Cache for 5 minutes
}

$cache_key_posts = 'kcg_total_posts_indexed';
$total_posts_indexed = wp_cache_get($cache_key_posts, 'kcg_ai_chatbot');

if (false === $total_posts_indexed) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with caching
    $total_posts_indexed = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT source_id) FROM " . esc_sql($vector_table) . " WHERE source = %s",
        'post'
    ));
    wp_cache_set($cache_key_posts, $total_posts_indexed, 'kcg_ai_chatbot', 300); // Cache for 5 minutes
}

$post_types = get_post_types(['public' => true], 'objects');

$per_page = 10;
$current_page = 1;
$selected_post_type = 'page';
$search_query = '';

if (isset($_GET['kcg_filter_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['kcg_filter_nonce'])), 'kcg_filter_knowledge')) {
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $selected_post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : 'page';
    if (!empty($_GET['s'])) {
        $search_query = sanitize_text_field(wp_unslash($_GET['s']));
    }
}

$args = [
    'post_type' => $selected_post_type,
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $current_page,
    'orderby' => 'modified',
    'order' => 'DESC'
];

if (!empty($search_query)) {
    $args['s'] = $search_query;
}

$query = new WP_Query($args);
$total_items = $query->found_posts;
$total_pages = $query->max_num_pages;

$indexed_posts = [];
if ($query->have_posts()) {
    $post_ids = wp_list_pluck($query->posts, 'ID');
    
    $cache_key_indexed = 'kcg_indexed_posts_' . md5(serialize($post_ids));
    $indexed_results = wp_cache_get($cache_key_indexed, 'kcg_ai_chatbot');
    
    if (false === $indexed_results) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        
        $query_template = sprintf(
            "SELECT DISTINCT source_id FROM %s WHERE source = %%s AND source_id IN (%s)",
            esc_sql($vector_table),
            $placeholders
        );
        
        $prepare_args = array_merge(['post'], $post_ids);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with caching
        $indexed_results = $wpdb->get_col(
            $wpdb->prepare($query_template, $prepare_args)
        );
        
        wp_cache_set($cache_key_indexed, $indexed_results, 'kcg_ai_chatbot', 300); // Cache for 5 minutes
    }
    
    $indexed_posts = array_flip($indexed_results);
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Knowledge Base Management', 'kcg-ai-chatbot'); ?></h1>
    <div class="kcg-stats-cards">
        <div class="kcg-stats-card">
            <div class="kcg-bulk-actions">
                <h3><?php esc_html_e('Bulk Indexing', 'kcg-ai-chatbot'); ?></h3>
                <p><?php esc_html_e('Index all published content at once. This may take several minutes depending on the amount of content.', 'kcg-ai-chatbot'); ?></p>
            
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                    <button type="button" class="button button-primary kcg-process-all-posts" data-post-types='["post"]'>
                        <?php esc_html_e('Index All Posts', 'kcg-ai-chatbot'); ?>
                    </button>
                    
                    <button type="button" class="button button-primary kcg-process-all-posts" data-post-types='["page"]'>
                        <?php esc_html_e('Index All Pages', 'kcg-ai-chatbot'); ?>
                    </button>
                    
                    <?php
                    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
                    if (!empty($custom_post_types)) {
                        foreach ($custom_post_types as $post_type_obj) {
                            printf(
                                '<button type="button" class="button button-primary kcg-process-all-posts" data-post-types=\'["%s"]\'>%s</button>',
                                esc_attr($post_type_obj->name),
                                sprintf(esc_html__('Index All %s', 'kcg-ai-chatbot'), esc_html($post_type_obj->labels->name))
                            );
                        }
                    }
                    ?>
                </div>
            
                <div id="kcg-bulk-progress" style="margin-top: 15px; display: none;">
                    <div style="background: #f0f0f1; border-radius: 4px; padding: 10px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php esc_html_e('Processing...', 'kcg-ai-chatbot'); ?></div>
                        <div id="kcg-bulk-status"><?php esc_html_e('Initializing...', 'kcg-ai-chatbot'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="kcg-stats-card">
            <h3 style="margin-top: 0;"><?php esc_html_e('All Indexed', 'kcg-ai-chatbot'); ?></h3>
            <p style="font-size: 24px; font-weight: bold; color: #764ba2;"><?php echo number_format($total_posts_indexed); ?> </p>
        </div>
    </div>
    
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr(isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : ''); ?>">
        <input type="hidden" name="tab" value="knowledge">
        <?php wp_nonce_field('kcg_filter_knowledge', 'kcg_filter_nonce'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="post_type" id="post_type">
                    <?php foreach ($post_types as $post_type_obj): ?>
                        <option value="<?php echo esc_attr($post_type_obj->name); ?>" <?php selected($selected_post_type, $post_type_obj->name); ?>>
                            <?php echo esc_html($post_type_obj->labels->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Search posts...', 'kcg-ai-chatbot'); ?>">
                <input type="submit" class="button" value="<?php esc_html_e('Filter', 'kcg-ai-chatbot'); ?>">
            </div>
        </div>
    </form>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Title', 'kcg-ai-chatbot'); ?></th>
                <th style="width: 120px;"><?php esc_html_e('Type', 'kcg-ai-chatbot'); ?></th>
                <th style="width: 120px;"><?php esc_html_e('Status', 'kcg-ai-chatbot'); ?></th>
                <th style="width: 150px;"><?php esc_html_e('Last Modified', 'kcg-ai-chatbot'); ?></th>
                <th style="width: 150px;"><?php esc_html_e('Actions', 'kcg-ai-chatbot'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query->have_posts()): ?>
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <?php
                    $post_id = get_the_ID();
                    $post_type_object = get_post_type_object(get_post_type());
                    $is_indexed = isset($indexed_posts[$post_id]);
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <a href="<?php the_permalink(); ?>" target="_blank"><?php esc_html_e('View', 'kcg-ai-chatbot'); ?></a>
                            </div>
                        </td>
                        <td><?php echo esc_html($post_type_object->labels->singular_name); ?></td>
                        <td>
                            <?php if ($is_indexed): ?>
                                <span style="color: #10b981;">✓ <?php esc_html_e('Indexed', 'kcg-ai-chatbot'); ?></span>
                            <?php else: ?>
                                <span style="color: #6b7280;">- <?php esc_html_e('Not Indexed', 'kcg-ai-chatbot'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(get_the_modified_date()); ?></td>
                        <td style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                            <button type="button" class="button button-small kcg-process-single" data-post-id="<?php echo esc_attr($post_id); ?>">
                                <?php echo $is_indexed ? esc_html__('Re-index', 'kcg-ai-chatbot') : esc_html__('Index', 'kcg-ai-chatbot'); ?>
                            </button>
                            <button type="button" class="button button-small kcg-unindex-single <?php echo !$is_indexed ? 'disabled' : ''; ?>" 
                                    data-post-id="<?php echo esc_attr($post_id); ?>" 
                                    style="background: #dc3232; color: white; border-color: #dc3232;" 
                                    <?php echo !$is_indexed ? 'disabled' : ''; ?>>
                                <?php esc_html_e('Unindex', 'kcg-ai-chatbot'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No posts found.', 'kcg-ai-chatbot'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php wp_reset_postdata(); ?>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages kcg-pagination">
                <span class="displaying-num">
                    <?php
                        printf(_n('%s item', '%s items', $total_items, 'kcg-ai-chatbot'), number_format_i18n($total_items)); 
                    ?>
                </span>
                <span class="pagination-links">
                    <?php
                    echo wp_kses_post(paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;', 'kcg-ai-chatbot'),
                        'next_text' => __('&raquo;', 'kcg-ai-chatbot'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain',
                    )));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>