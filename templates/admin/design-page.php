<?php 
if (!defined('ABSPATH')) {
    exit;
}

// Check for nonce and permissions
if (isset($_POST['kcg_ai_chatbot_save_design']) && current_user_can('manage_options')) {
    // Verify nonce
    if (check_admin_referer('kcg_ai_chatbot_design_action', 'kcg_ai_chatbot_design_nonce')) {
        // Sanitize and save color inputs
        $header_bg = isset($_POST['kcg_ai_chatbot_header_bg_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_header_bg_color']) : '';
        $header_text = isset($_POST['kcg_ai_chatbot_header_text_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_header_text_color']) : '';
        $user_msg_bg = isset($_POST['kcg_ai_chatbot_user_msg_bg_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_user_msg_bg_color']) : '';
        $user_msg_text = isset($_POST['kcg_ai_chatbot_user_msg_text_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_user_msg_text_color']) : '';
        $bot_msg_bg = isset($_POST['kcg_ai_chatbot_bot_msg_bg_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_bot_msg_bg_color']) : '';
        $bot_msg_text = isset($_POST['kcg_ai_chatbot_bot_msg_text_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_bot_msg_text_color']) : '';
        $button_bg = isset($_POST['kcg_ai_chatbot_button_bg_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_button_bg_color']) : '';
        $button_text = isset($_POST['kcg_ai_chatbot_button_text_color']) ? sanitize_hex_color($_POST['kcg_ai_chatbot_button_text_color']) : '';
        
        // Update options
        update_option('kcg_ai_chatbot_header_bg_color', $header_bg);
        update_option('kcg_ai_chatbot_header_text_color', $header_text);
        update_option('kcg_ai_chatbot_user_msg_bg_color', $user_msg_bg);
        update_option('kcg_ai_chatbot_user_msg_text_color', $user_msg_text);
        update_option('kcg_ai_chatbot_bot_msg_bg_color', $bot_msg_bg);
        update_option('kcg_ai_chatbot_bot_msg_text_color', $bot_msg_text);
        update_option('kcg_ai_chatbot_button_bg_color', $button_bg);
        update_option('kcg_ai_chatbot_button_text_color', $button_text);
        
        // Update CSS file via the design manager
        if (class_exists('KCG_AI_Chatbot_Design_Manager')) {
            $result = KCG_AI_Chatbot_Design_Manager::update_css_file();
            
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html__('Design settings saved successfully!', 'kaichat') . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html__('Design settings saved successfully, but CSS file could not be updated.', 'kaichat') . '</p></div>';
        }
    }
}

// Get current color settings with defaults
$header_bg = get_option('kcg_ai_chatbot_header_bg_color', '#667eea');
$header_text = get_option('kcg_ai_chatbot_header_text_color', '#ffffff');
$user_msg_bg = get_option('kcg_ai_chatbot_user_msg_bg_color', '#667eea');
$user_msg_text = get_option('kcg_ai_chatbot_user_msg_text_color', '#ffffff');
$bot_msg_bg = get_option('kcg_ai_chatbot_bot_msg_bg_color', '#ffffff');
$bot_msg_text = get_option('kcg_ai_chatbot_bot_msg_text_color', '#1f2937');
$button_bg = get_option('kcg_ai_chatbot_button_bg_color', '#4F46E5');
$button_text = get_option('kcg_ai_chatbot_button_text_color', '#ffffff');

// Ensure color picker scripts are loaded
wp_enqueue_style('wp-color-picker');
wp_enqueue_script('wp-color-picker');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kcg-design-container" style="display: flex; gap: 30px;">
        <div class="kcg-design-settings" style="flex: 1;">
            <form method="post" action="">
                <?php wp_nonce_field('kcg_ai_chatbot_design_action', 'kcg_ai_chatbot_design_nonce'); ?>
                
                <h2><?php _e('Chatbot Appearance', 'kaichat'); ?></h2>
                <p><?php _e('Customize the colors of your chatbot to match your website design.', 'kaichat'); ?></p>
                
                <h3><?php _e('Header Colors', 'kaichat'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_header_bg_color"><?php _e('Header Background', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_header_bg_color" id="kcg_ai_chatbot_header_bg_color" 
                                value="<?php echo esc_attr($header_bg); ?>" class="kcg-color-field" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_header_text_color"><?php _e('Header Text', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_header_text_color" id="kcg_ai_chatbot_header_text_color" 
                                value="<?php echo esc_attr($header_text); ?>" class="kcg-color-field" />
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Button Colors', 'kaichat'); ?></h3>
                <p class="description"><?php _e('These colors will be applied to both the chat button and the send button.', 'kaichat'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_button_bg_color"><?php _e('Button Background', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_button_bg_color" id="kcg_ai_chatbot_button_bg_color" 
                                value="<?php echo esc_attr($button_bg); ?>" class="kcg-color-field" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_button_text_color"><?php _e('Button Icon', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_button_text_color" id="kcg_ai_chatbot_button_text_color" 
                                value="<?php echo esc_attr($button_text); ?>" class="kcg-color-field" />
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('User Message Colors', 'kaichat'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_user_msg_bg_color"><?php _e('Background', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_user_msg_bg_color" id="kcg_ai_chatbot_user_msg_bg_color" 
                                value="<?php echo esc_attr($user_msg_bg); ?>" class="kcg-color-field" />
                            <p class="description"><?php _e('This color will also be used for the user avatar background.', 'kaichat'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_user_msg_text_color"><?php _e('Text', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_user_msg_text_color" id="kcg_ai_chatbot_user_msg_text_color" 
                                value="<?php echo esc_attr($user_msg_text); ?>" class="kcg-color-field" />
                            <p class="description"><?php _e('This color will also be used for the user avatar icon.', 'kaichat'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Bot Message Colors', 'kaichat'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_bot_msg_bg_color"><?php _e('Background', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_bot_msg_bg_color" id="kcg_ai_chatbot_bot_msg_bg_color" 
                                value="<?php echo esc_attr($bot_msg_bg); ?>" class="kcg-color-field" />
                            <p class="description"><?php _e('This color will also be used for the bot avatar background.', 'kaichat'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kcg_ai_chatbot_bot_msg_text_color"><?php _e('Text', 'kaichat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="kcg_ai_chatbot_bot_msg_text_color" id="kcg_ai_chatbot_bot_msg_text_color" 
                                value="<?php echo esc_attr($bot_msg_text); ?>" class="kcg-color-field" />
                            <p class="description"><?php _e('This color will also be used for the bot avatar icon.', 'kaichat'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Design Settings', 'kaichat'), 'primary', 'kcg_ai_chatbot_save_design'); ?>
            </form>
        </div>
        
        <div class="kcg-design-preview" style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 380px;">
            <h3><?php _e('Live Preview', 'kaichat'); ?></h3>
            
            <!-- Chat Preview -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 15px;">
                <!-- Header Preview -->
                <div class="preview-header" style="padding: 15px; color: <?php echo esc_attr($header_text); ?>; background: <?php echo esc_attr($header_bg); ?>;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: bold;"><?php _e('AI Assistant', 'kaichat'); ?></div>
                            <div style="font-size: 12px; opacity: 0.9;">
                                <span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; display: inline-block; margin-right: 5px;"></span>
                                <?php _e('Online', 'kaichat'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Messages Preview -->
                <div style="padding: 15px; min-height: 250px;">
                    <!-- Bot Message -->
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <div class="preview-bot-avatar" style="width: 32px; height: 32px; background: <?php echo esc_attr($bot_msg_bg); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: <?php echo esc_attr($bot_msg_text); ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </div>
                        <div style="max-width: 75%;">
                            <div class="preview-bot-message" style="background: <?php echo esc_attr($bot_msg_bg); ?>; color: <?php echo esc_attr($bot_msg_text); ?>; padding: 10px 12px; border-radius: 12px; border-bottom-left-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <?php _e('Hello! How can I help you today?', 'kaichat'); ?>
                            </div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px; padding: 0 4px;">12:45 PM</div>
                        </div>
                    </div>
                    
                    <!-- User Message -->
                    <div style="display: flex; flex-direction: row-reverse; gap: 10px; margin-bottom: 15px;">
                        <div class="preview-user-avatar" style="width: 32px; height: 32px; background: <?php echo esc_attr($user_msg_bg); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: <?php echo esc_attr($user_msg_text); ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div style="max-width: 75%; display: flex; flex-direction: column; align-items: flex-end;">
                            <div class="preview-user-message" style="background: <?php echo esc_attr($user_msg_bg); ?>; color: <?php echo esc_attr($user_msg_text); ?>; padding: 10px 12px; border-radius: 12px; border-bottom-right-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <?php _e('I have a question about your services.', 'kaichat'); ?>
                            </div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px; padding: 0 4px;">12:46 PM</div>
                        </div>
                    </div>
                </div>
                
                <!-- Input Preview -->
                <div style="padding: 15px; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <div style="flex: 1; border: 1px solid #d1d5db; border-radius: 12px; padding: 10px; min-height: 24px; background: white;">
                            <?php _e('Type your message...', 'kaichat'); ?>
                        </div>
                        <div class="preview-send-button" style="width: 40px; height: 40px; background: <?php echo esc_attr($button_bg); ?>; border-radius: 50%; color: <?php echo esc_attr($button_text); ?>; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr($button_text); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Button Preview -->
            <div style="margin-top: 20px;">
                <p><strong><?php _e('Chat Button', 'kaichat'); ?></strong></p>
                <div class="preview-chat-button" style="width: 60px; height: 60px; border-radius: 50%; background: <?php echo esc_attr($button_bg); ?>; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center; margin: 10px 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr($button_text); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>