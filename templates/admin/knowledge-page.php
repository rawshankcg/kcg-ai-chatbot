<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;
$vector_table = $wpdb->prefix . 'kcg_ai_chatbot_vectors';
$total_vectors = $wpdb->get_var("SELECT COUNT(*) FROM $vector_table");
$total_posts_indexed = $wpdb->get_var("SELECT COUNT(DISTINCT source_id) FROM $vector_table WHERE source = 'post'");

$post_types = get_post_types(['public' => true], 'objects');

$per_page = 10;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$selected_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'page';

$args = [
    'post_type' => $selected_post_type,
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $current_page,
    'orderby' => 'modified',
    'order' => 'DESC'
];

if (!empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

$query = new WP_Query($args);
$total_items = $query->found_posts;
$total_pages = $query->max_num_pages;

$indexed_posts = [];
if ($query->have_posts()) {
    $post_ids = wp_list_pluck($query->posts, 'ID');
    $indexed_results = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT source_id FROM $vector_table WHERE source = 'post' AND source_id IN (" .
        implode(',', array_fill(0, count($post_ids), '%d')) . ")",
        ...$post_ids
    ));
    $indexed_posts = array_flip($indexed_results);
}
?>

<div class="wrap">
    <h1><?php _e('Knowledge Base Management', 'kaichat'); ?></h1>

    <div class="kcg-stats-cards">
        <div class="kcg-stats-card">
            <div class="kcg-bulk-actions">
                <h3><?php _e('Bulk Indexing', 'kaichat'); ?></h3>
                <p><?php _e('Index all published content at once. This may take several minutes depending on the amount of content.', 'kaichat'); ?></p>
            
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                    <button type="button" class="button button-primary kcg-process-all-posts" data-post-types='["post"]'>
                        <?php _e('Index All Posts', 'kaichat'); ?>
                    </button>
                    
                    <button type="button" class="button button-primary kcg-process-all-posts" data-post-types='["page"]'>
                        <?php _e('Index All Pages', 'kaichat'); ?>
                    </button>
                    
                    <?php
                    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
                    if (!empty($custom_post_types)) {
                        foreach ($custom_post_types as $post_type_obj) {
                            printf(
                                '<button type="button" class="button button-primary kcg-process-all-posts" data-post-types=\'["%s"]\'>%s</button>',
                                esc_attr($post_type_obj->name),
                                sprintf(__('Index All %s', 'kaichat'), esc_html($post_type_obj->labels->name))
                            );
                        }
                    }
                    ?>
                </div>
            
                <div id="kcg-bulk-progress" style="margin-top: 15px; display: none;">
                    <div style="background: #f0f0f1; border-radius: 4px; padding: 10px;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php _e('Processing...', 'kaichat'); ?></div>
                        <div id="kcg-bulk-status"><?php _e('Initializing...', 'kaichat'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kcg-stats-card">
            <h3 style="margin-top: 0;"><?php _e('All Indexed', 'kaichat'); ?></h3>
            <p style="font-size: 24px; font-weight: bold; color: #764ba2;"><?php echo number_format($total_posts_indexed); ?> <?php echo $total_vectors; ?></p>
        </div>
        
    </div>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <input type="hidden" name="tab" value="knowledge">

        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="post_type" id="post_type">
                    <?php foreach ($post_types as $post_type_obj): ?>
                        <option value="<?php echo esc_attr($post_type_obj->name); ?>" <?php selected($selected_post_type, $post_type_obj->name); ?>>
                            <?php echo esc_html($post_type_obj->labels->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php esc_attr_e('Search posts...', 'kaichat'); ?>">
                <input type="submit" class="button" value="<?php _e('Filter', 'kaichat'); ?>">
            </div>
        </div>
    </form>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Title', 'kaichat'); ?></th>
                <th style="width: 120px;"><?php _e('Type', 'kaichat'); ?></th>
                <th style="width: 120px;"><?php _e('Status', 'kaichat'); ?></th>
                <th style="width: 150px;"><?php _e('Last Modified', 'kaichat'); ?></th>
                <th style="width: 150px;"><?php _e('Actions', 'kaichat'); ?></th>
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
                            <strong><a href="<?php echo get_edit_post_link($post_id); ?>"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <a href="<?php the_permalink(); ?>" target="_blank"><?php _e('View', 'kaichat'); ?></a>
                            </div>
                        </td>
                        <td><?php echo esc_html($post_type_object->labels->singular_name); ?></td>
                        <td>
                            <?php if ($is_indexed): ?>
                                <span style="color: #10b981;">✓ <?php _e('Indexed', 'kaichat'); ?></span>
                            <?php else: ?>
                                <span style="color: #6b7280;">- <?php _e('Not Indexed', 'kaichat'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo get_the_modified_date(); ?></td>
                        <td style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                            <button type="button" class="button button-small kcg-process-single" data-post-id="<?php echo $post_id; ?>">
                                <?php echo $is_indexed ? __('Re-index', 'kaichat') : __('Index', 'kaichat'); ?>
                            </button>
                            <button type="button" class="button button-small kcg-unindex-single <?php echo !$is_indexed ? 'disabled' : ''; ?>" 
                                    data-post-id="<?php echo $post_id; ?>" 
                                    style="background: #dc3232; color: white; border-color: #dc3232;" 
                                    <?php echo !$is_indexed ? 'disabled' : ''; ?>>
                                <?php _e('Unindex', 'kaichat'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php _e('No posts found.', 'kaichat'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php wp_reset_postdata(); ?>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages kcg-pagination">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'kaichat'), number_format_i18n($total_items)); ?></span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain',
                    ));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>