<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle bulk indexing
if (isset($_POST['kcg_process_content']) && isset($_POST['post_ids'])) {
    check_admin_referer('kcg_knowledge_base_action', 'kcg_knowledge_base_nonce');

    $post_ids = array_map('intval', $_POST['post_ids']);
    $selected_types = isset($_POST['index_post_types']) ? (array) $_POST['index_post_types'] : [];

    // Only keep posts of selected types
    if (!empty($selected_types)) {
        $post_ids = array_filter($post_ids, function ($id) use ($selected_types) {
            return in_array(get_post_type($id), $selected_types);
        });
    } else {
        $post_ids = [];
    }

    if (!empty($post_ids)) {
        $processor = new KCG_AI_Content_Processor();
        $results = $processor->process_bulk_posts($post_ids);
        echo '<div class="notice notice-success"><p>';
        printf(__('Processed %d posts successfully. %d failed.', 'kaichat'),
            $results['processed'], $results['failed']);
        echo '</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>';
        _e('No posts selected for indexing based on post type filter.', 'kaichat');
        echo '</p></div>';
    }
}

// Get statistics
global $wpdb;
$vector_table = $wpdb->prefix . 'kcg_ai_chatbot_vectors';
$total_vectors = $wpdb->get_var("SELECT COUNT(*) FROM $vector_table");
$total_posts_indexed = $wpdb->get_var("SELECT COUNT(DISTINCT source_id) FROM $vector_table WHERE source = 'post'");

// Get post types
$post_types = get_post_types(['public' => true], 'objects');
$selected_index_types = $_POST['index_post_types'] ?? [];

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Selected post type filter for the table
$selected_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'any';

// Query posts
$args = [
    'post_type' => $selected_post_type,
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'offset' => $offset,
    'orderby' => 'modified',
    'order' => 'DESC'
];

if (!empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

$query = new WP_Query($args);
$total_pages = $query->max_num_pages;

// Check which posts are already indexed
$indexed_posts = [];
if ($query->have_posts()) {
    $post_ids = wp_list_pluck($query->posts, 'ID');
    $indexed = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT source_id FROM $vector_table WHERE source = 'post' AND source_id IN (" .
        implode(',', array_fill(0, count($post_ids), '%d')) . ")",
        ...$post_ids
    ));
    $indexed_posts = array_flip($indexed);
}
?>

<div class="wrap">
    <h1><?php _e('Knowledge Base Management', 'kaichat'); ?></h1>

    <!-- Statistics -->
    <div class="kcg-stats-cards" style="display: flex; gap: 20px; margin: 20px 0;">
        <div class="card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; flex: 1;">
            <h3 style="margin-top: 0;"><?php _e('Total Vectors', 'kaichat'); ?></h3>
            <p style="font-size: 24px; font-weight: bold; color: #667eea;"><?php echo number_format($total_vectors); ?></p>
        </div>
        <div class="card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; flex: 1;">
            <h3 style="margin-top: 0;"><?php _e('Posts Indexed', 'kaichat'); ?></h3>
            <p style="font-size: 24px; font-weight: bold; color: #764ba2;"><?php echo number_format($total_posts_indexed); ?></p>
        </div>
    </div>

    <!-- Post Type Checkboxes -->
    <form method="post" action="">
        <?php wp_nonce_field('kcg_knowledge_base_action', 'kcg_knowledge_base_nonce'); ?>
        <div class="kcg-post-type-filters" style="margin-bottom: 20px;">
            <strong><?php _e('Select Post Types to Index:', 'kaichat'); ?></strong><br>
            <?php foreach ($post_types as $type): ?>
                <label style="margin-right: 15px;">
                    <input type="checkbox" name="index_post_types[]" value="<?php echo esc_attr($type->name); ?>"
                        <?php checked(in_array($type->name, $selected_index_types)); ?>>
                    <?php echo esc_html($type->labels->singular_name); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Filter Table -->
        <div class="tablenav top">
            <input type="hidden" name="page" value="kcg-ai-chatbot">
            <input type="hidden" name="tab" value="knowledge">

            <div class="alignleft actions">
                <select name="post_type" id="post_type">
                    <option value="any"><?php _e('All Post Types', 'kaichat'); ?></option>
                    <?php foreach ($post_types as $post_type): ?>
                        <option value="<?php echo esc_attr($post_type->name); ?>"
                            <?php selected($selected_post_type, $post_type->name); ?>>
                            <?php echo esc_html($post_type->labels->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('Search posts...', 'kaichat'); ?>">

                <?php submit_button(__('Filter', 'kaichat'), 'button', '', false); ?>
            </div>
        </div>

        <!-- Posts Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th><?php _e('Title', 'kaichat'); ?></th>
                    <th style="width: 100px;"><?php _e('Type', 'kaichat'); ?></th>
                    <th style="width: 120px;"><?php _e('Status', 'kaichat'); ?></th>
                    <th style="width: 150px;"><?php _e('Modified', 'kaichat'); ?></th>
                    <th style="width: 120px;"><?php _e('Actions', 'kaichat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($query->have_posts()): ?>
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $post_type_name = get_post_type();
                        $is_indexed = isset($indexed_posts[$post_id]);
                        ?>
                        <tr data-post-type="<?php echo esc_attr($post_type_name); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post_ids[]" value="<?php echo $post_id; ?>">
                            </th>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($post_id); ?>"><?php the_title(); ?></a>
                                </strong>
                                <div class="row-actions">
                                    <a href="<?php the_permalink(); ?>" target="_blank"><?php _e('View', 'kaichat'); ?></a>
                                </div>
                            </td>
                            <td><?php echo get_post_type_object($post_type_name)->labels->singular_name; ?></td>
                            <td>
                                <?php if ($is_indexed): ?>
                                    <span style="color: #10b981;">✓ <?php _e('Indexed', 'kaichat'); ?></span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">- <?php _e('Not indexed', 'kaichat'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_the_modified_date(); ?></td>
                            <td>
                                <button type="button" class="button button-small kcg-process-single"
                                    data-post-id="<?php echo $post_id; ?>">
                                    <?php echo $is_indexed ? __('Re-index', 'kaichat') : __('Index', 'kaichat'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('No posts found.', 'kaichat'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php wp_reset_postdata(); ?>

        <!-- Bulk Actions -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <button type="submit" name="kcg_process_content" class="button button-primary">
                    <?php _e('Index Selected Posts', 'kaichat'); ?>
                </button>

                <button type="button" id="kcg-index-all" class="button">
                    <?php _e('Index All Visible Posts', 'kaichat'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="post_ids[]"]').prop('checked', $(this).is(':checked'));
    });

    // Index all visible posts button
    $('#kcg-index-all').on('click', function() {
        var selectedTypes = $('input[name="index_post_types[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        $('input[name="post_ids[]"]').each(function() {
            var rowType = $(this).closest('tr').data('post-type');
            $(this).prop('checked', selectedTypes.includes(rowType));
        });

        $(this).closest('form').submit();
    });

    // AJAX single post indexing
    $('.kcg-process-single').on('click', function() {
        var button = $(this);
        var postId = button.data('post-id');

        button.prop('disabled', true).text('Processing...');

        $.post(ajaxurl, {
            action: 'kcg_process_single_post',
            post_id: postId,
            nonce: '<?php echo wp_create_nonce('kcg_process_single'); ?>'
        }, function(response) {
            if (response.success) {
                button.text('Re-index');
                button.closest('tr').find('td:eq(2)').html('<span style="color: #10b981;">✓ Indexed</span>');
            } else {
                alert('Error: ' + response.data);
            }
            button.prop('disabled', false);
        });
    });
});
</script>
