<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count
$total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total_conversations / $per_page);

// Get conversations
$conversations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

// Delete conversation if requested
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    check_admin_referer('delete_conversation_' . $_GET['id']);
    $wpdb->delete($table_name, array('id' => intval($_GET['id'])), array('%d'));
    echo '<div class="notice notice-success"><p>' . __('Conversation deleted successfully!', 'kaichat') . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php _e('Chat Conversations', 'kaichat'); ?></h1>
    
    <p><?php printf(__('Total Conversations: %d', 'kaichat'), $total_conversations); ?></p>
    
    <?php if (empty($conversations)): ?>
        <p><?php _e('No conversations found.', 'kaichat'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'kaichat'); ?></th>
                    <th style="width: 150px;"><?php _e('Session ID', 'kaichat'); ?></th>
                    <th style="width: 100px;"><?php _e('User', 'kaichat'); ?></th>
                    <th><?php _e('User Message', 'kaichat'); ?></th>
                    <th><?php _e('Bot Response', 'kaichat'); ?></th>
                    <th style="width: 80px;"><?php _e('Tokens', 'kaichat'); ?></th>
                    <th style="width: 150px;"><?php _e('Date', 'kaichat'); ?></th>
                    <th style="width: 80px;"><?php _e('Actions', 'kaichat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conversations as $conversation): ?>
                    <tr>
                        <td><?php echo esc_html($conversation->id); ?></td>
                        <td>
                            <code style="font-size: 11px;">
                                <?php echo esc_html(substr($conversation->session_id, 0, 15)); ?>...
                            </code>
                        </td>
                        <td>
                            <?php 
                            if ($conversation->user_id) {
                                $user = get_userdata($conversation->user_id);
                                echo esc_html($user ? $user->display_name : 'Unknown');
                            } else {
                                echo '<em>' . __('Guest', 'kaichat') . '</em>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(wp_trim_words($conversation->user_message, 10)); ?></td>
                        <td><?php echo esc_html(wp_trim_words($conversation->bot_response, 10)); ?></td>
                        <td><?php echo esc_html($conversation->tokens_used); ?></td>
                        <td><?php echo esc_html(mysql2date('Y-m-d H:i', $conversation->created_at)); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin.php?page=kcg-ai-chatbot-conversations&action=delete&id=' . $conversation->id),
                                'delete_conversation_' . $conversation->id
                            ); ?>" 
                            class="button button-small button-link-delete"
                            onclick="return confirm('<?php _e('Are you sure you want to delete this conversation?', 'kaichat'); ?>');">
                                <?php _e('Delete', 'kaichat'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous', 'kaichat'),
                        'next_text' => __('Next &raquo;', 'kaichat'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>