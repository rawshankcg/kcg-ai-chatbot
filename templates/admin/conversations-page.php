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

// Group conversations by session
$grouped_conversations = [];
foreach ($conversations as $conversation) {
    $grouped_conversations[$conversation->session_id][] = $conversation;
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
    
    <!-- Toggle All Button -->
    <div style="margin: 15px 0;">
        <button type="button" id="kcg-toggle-all-sessions" class="button button-secondary">
            <?php _e('Expand All Sessions', 'kaichat'); ?>
        </button>
    </div>
    
    <?php if (empty($conversations)): ?>
        <p><?php _e('No conversations found.', 'kaichat'); ?></p>
    <?php else: ?>
        
        <div class="kcg-conversation-sessions">
            <?php
            $session_index = 0;
            foreach ($grouped_conversations as $session_id => $session_conversations):
                $first_conversation = $session_conversations[0];
                $user = $first_conversation->user_id ? get_userdata($first_conversation->user_id) : null;
                $user_display_name = $user ? $user->display_name : __('Guest', 'kaichat');
                $total_messages = count($session_conversations);
                $session_start = mysql2date('Y-m-d H:i:s', $first_conversation->created_at);
                $session_end = mysql2date('Y-m-d H:i:s', end($session_conversations)->created_at);
                $total_tokens = array_sum(array_column($session_conversations, 'tokens_used'));
            ?>
                <div class="kcg-session-container" style="margin-bottom: 20px; border: 1px solid #c3c4c7; border-radius: 6px; background: white;">
                    
                    <!-- Session Header (Clickable) -->
                    <div class="kcg-session-header" 
                         data-session-id="<?php echo esc_attr($session_id); ?>"
                         style="background: #f6f7f7; padding: 15px 20px; cursor: pointer; border-bottom: 1px solid #ddd; position: relative;">
                        
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="kcg-toggle-icon dashicons dashicons-arrow-right-alt2" 
                                          style="font-size: 16px; color: #646970; transition: transform 0.2s;"></span>
                                    <strong style="font-size: 14px; color: #1d2327;">
                                        <?php printf(__('Session: %s', 'kaichat'), '<code>' . esc_html(substr($session_id, -8)) . '</code>'); ?>
                                    </strong>
                                    <span class="kcg-session-badge" 
                                          style="background: #2271b1; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px;">
                                        <?php printf(__('%d messages', 'kaichat'), $total_messages); ?>
                                    </span>
                                </div>
                                
                                <div style="margin-top: 8px; font-size: 13px; color: #646970;">
                                    <span><strong><?php _e('User:', 'kaichat'); ?></strong> <?php echo esc_html($user_display_name); ?></span>
                                    <span style="margin-left: 15px;"><strong><?php _e('Started:', 'kaichat'); ?></strong> <?php echo esc_html($session_start); ?></span>
                                    <?php if ($total_tokens > 0): ?>
                                        <span style="margin-left: 15px;"><strong><?php _e('Tokens:', 'kaichat'); ?></strong> <?php echo number_format($total_tokens); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="kcg-session-actions" style="opacity: 0.7;">
                                <button type="button" class="button-link kcg-delete-session" 
                                        data-session-id="<?php echo esc_attr($session_id); ?>"
                                        style="color: #b32d2e; text-decoration: none;"
                                        onclick="event.stopPropagation();"
                                        title="<?php esc_attr_e('Delete entire session', 'kaichat'); ?>">
                                    <span class="dashicons dashicons-trash" style="font-size: 16px;"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Session Content (Collapsible) -->
                    <div class="kcg-session-content" 
                         data-session-id="<?php echo esc_attr($session_id); ?>"
                         style="display: none; max-height: 0; overflow: hidden; transition: all 0.3s ease;">
                        
                        <div style="padding: 0;">
                            <table class="wp-list-table widefat striped" style="margin: 0; border: none;">
                                <thead>
                                    <tr style="background: #f9f9f9;">
                                        <th style="width: 150px; padding: 10px 15px;"><?php _e('Time', 'kaichat'); ?></th>
                                        <th style="padding: 10px 15px;"><?php _e('User Message', 'kaichat'); ?></th>
                                        <th style="padding: 10px 15px;"><?php _e('Bot Response', 'kaichat'); ?></th>
                                        <th style="width: 80px; padding: 10px 15px;"><?php _e('Actions', 'kaichat'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($session_conversations as $conversation): ?>
                                        <tr>
                                            <td style="padding: 12px 15px; vertical-align: top;">
                                                <small style="color: #646970;">
                                                    <?php echo esc_html(mysql2date('H:i:s', $conversation->created_at)); ?>
                                                </small>
                                                <?php if ($conversation->tokens_used): ?>
                                                    <br><small style="color: #2271b1;"><?php printf(__('%d tokens', 'kaichat'), intval($conversation->tokens_used)); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 12px 15px; vertical-align: top;">
                                                <div style="max-width: 300px; word-wrap: break-word;">
                                                    <?php echo esc_html($conversation->user_message); ?>
                                                </div>
                                            </td>
                                            <td style="padding: 12px 15px; vertical-align: top;">
                                                <div style="max-width: 400px; word-wrap: break-word;">
                                                    <?php echo esc_html($conversation->bot_response); ?>
                                                </div>
                                            </td>
                                            <td style="padding: 12px 15px; text-align: center; vertical-align: top;">
                                                <a href="<?php echo wp_nonce_url(
                                                    admin_url('admin.php?page=kcg-ai-chatbot&tab=conversations&action=delete&id=' . $conversation->id . '&paged=' . $current_page),
                                                    'delete_conversation_' . $conversation->id
                                                ); ?>" 
                                                class="button-link-delete"
                                                style="color: #b32d2e; text-decoration: none;"
                                                onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this message?', 'kaichat'); ?>');"
                                                title="<?php esc_attr_e('Delete this message', 'kaichat'); ?>">
                                                    <span class="dashicons dashicons-dismiss" style="font-size: 16px;"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
            <?php 
            $session_index++;
            endforeach; 
            ?>
        </div>
        
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