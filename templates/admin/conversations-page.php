<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'kcg_ai_chatbot_conversations';


$per_page = 5;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$total_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name");
$total_pages = ceil($total_sessions / $per_page);

$session_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT session_id FROM $table_name ORDER BY session_id DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

$conversations = [];
if (!empty($session_ids)) {
    $session_ids_placeholder = implode(', ', array_fill(0, count($session_ids), '%s'));
    $conversations = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE session_id IN ($session_ids_placeholder) ORDER BY session_id DESC, created_at ASC",
        ...$session_ids
    ));
}



if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    check_admin_referer('delete_conversation_' . $_GET['id']);
    $wpdb->delete($table_name, array('id' => intval($_GET['id'])), array('%d'));
    echo '<div class="notice notice-success"><p>' . __('Conversation entry deleted successfully!', 'kaichat') . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php _e('Chat Conversations', 'kaichat'); ?></h1>
    
    <p><?php printf(__('Displaying %d sessions on this page.', 'kaichat'), count($session_ids)); ?></p>
    
    <?php if (empty($conversations)): ?>
        <p><?php _e('No conversations found.', 'kaichat'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 180px;"><?php _e('Session / Time', 'kaichat'); ?></th>
                    <th style="width: 120px;"><?php _e('User', 'kaichat'); ?></th>
                    <th><?php _e('User Message', 'kaichat'); ?></th>
                    <th><?php _e('Bot Response', 'kaichat'); ?></th>
                    <th style="width: 80px;"><?php _e('Actions', 'kaichat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_session_id = null;
                foreach ($conversations as $conversation):
                    if ($conversation->session_id !== $current_session_id) {
                        $current_session_id = $conversation->session_id;
                        $user = $conversation->user_id ? get_userdata($conversation->user_id) : null;
                        $user_display_name = $user ? $user->display_name : __('Guest', 'kaichat');
                        ?>
                        <tr class="session-header" style="background-color: #f0f0f1; border-top: 2px solid #c3c4c7;">
                            <td colspan="5">
                                <strong style="font-size: 14px;"><?php _e('Session:', 'kaichat'); ?></strong>
                                <code style="font-size: 13px;"><?php echo esc_html($conversation->session_id); ?></code>
                                <span style="margin-left: 10px;">(<?php echo esc_html($user_display_name); ?>)</span>
                            </td>
                        </tr>
                        <?php
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date('Y-m-d H:i:s', $conversation->created_at)); ?></td>
                        <td>
                            <?php 
                            if ($conversation->user_id) {
                                $user = get_userdata($conversation->user_id);
                                echo esc_html($user ? $user->display_name : 'Unknown');
                            } else {
                                echo '<em>' . __('Guest', 'kaichat') . '</em>';
                            }

                            if ($conversation->tokens_used) {
                                echo '<br><small style="color: #555;">' . sprintf(__('Tokens: %d', 'kaichat'), intval($conversation->tokens_used)) . '</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($conversation->user_message); ?></td>
                        <td><?php echo esc_html($conversation->bot_response); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin.php?page=kcg-ai-chatbot&tab=conversations&action=delete&id=' . $conversation->id . '&paged=' . $current_page),
                                'delete_conversation_' . $conversation->id
                            ); ?>" 
                            class="button-link-delete"
                            style="color: #b32d2e;"
                            onclick="return confirm('<?php _e('Are you sure you want to delete this single message?', 'kaichat'); ?>');">
                                <?php _e('Delete', 'kaichat'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages kcg-pagination">
                    <span class="displaying-num"><?php printf(_n('%s session', '%s sessions', $total_sessions, 'kaichat'), number_format_i18n($total_sessions)); ?></span>
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
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>