<?php 
if (!defined('ABSPATH')) {
    exit;
}

// Handle token reset
if (isset($_POST['kcg_reset_token_count']) && check_admin_referer('kcg_ai_chatbot_settings_action', 'kcg_ai_chatbot_settings_nonce')) {
    KCG_AI_Rest_Endpoints::reset_token_count();
    echo '<div class="notice notice-success"><p>' . __('Token count has been reset successfully!', 'kaichat') . '</p></div>';
}

// Save settings if form submitted
if (isset($_POST['kcg_ai_chatbot_save_settings'])) {
    check_admin_referer('kcg_ai_chatbot_settings_action', 'kcg_ai_chatbot_settings_nonce');
    
    update_option('kcg_ai_chatbot_enabled', isset($_POST['kcg_ai_chatbot_enabled']) ? 1 : 0);
    update_option('kcg_ai_chatbot_api_key', sanitize_text_field($_POST['kcg_ai_chatbot_api_key']));
    update_option('kcg_ai_chatbot_model', sanitize_text_field($_POST['kcg_ai_chatbot_model']));
    update_option('kcg_ai_chatbot_max_tokens', intval($_POST['kcg_ai_chatbot_max_tokens']));
    update_option('kcg_ai_chatbot_temperature', floatval($_POST['kcg_ai_chatbot_temperature']));
    update_option('kcg_ai_chatbot_welcome_message', sanitize_textarea_field($_POST['kcg_ai_chatbot_welcome_message']));
    update_option('kcg_ai_chatbot_instructions', sanitize_textarea_field($_POST['kcg_ai_chatbot_instructions']));
    
    // Knowledge Base Settings
    update_option('kcg_use_knowledge_base', isset($_POST['kcg_use_knowledge_base']) ? 1 : 0);
    update_option('kcg_chunk_size', intval($_POST['kcg_chunk_size']));
    
    // Display Settings
    update_option('kcg_show_on_homepage', isset($_POST['kcg_show_on_homepage']) ? 1 : 0);
    update_option('kcg_show_on_posts', isset($_POST['kcg_show_on_posts']) ? 1 : 0);
    update_option('kcg_show_on_pages', isset($_POST['kcg_show_on_pages']) ? 1 : 0);
    update_option('kcg_show_on_archives', isset($_POST['kcg_show_on_archives']) ? 1 : 0);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'kaichat') . '</p></div>';
}

$enabled = get_option('kcg_ai_chatbot_enabled', true);
$api_key = get_option('kcg_ai_chatbot_api_key', '');
$model = get_option('kcg_ai_chatbot_model', 'gemini-pro');
$max_tokens = get_option('kcg_ai_chatbot_max_tokens', 500);
$temperature = get_option('kcg_ai_chatbot_temperature', 0.7);
$welcome_message = get_option('kcg_ai_chatbot_welcome_message', 'Hello! How can I help you today?');
$instructions = get_option('kcg_ai_chatbot_instructions', '');

// Get token usage statistics
$total_tokens = intval(get_option('kcg_ai_chatbot_total_tokens', 0));
$token_limit = 10000;
$token_percentage = ($total_tokens / $token_limit) * 100;
$remaining_tokens = max(0, $token_limit - $total_tokens);
$token_status_color = $token_percentage >= 90 ? '#dc3232' : ($token_percentage >= 70 ? '#f0a500' : '#10b981');

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Token Usage Statistics -->
    <div class="kcg-token-stats-card" style="background: white; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 24px;"></span>
            <?php _e('Token Usage Statistics', 'kaichat'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 6px;">
                <div style="font-size: 28px; font-weight: bold; color: <?php echo esc_attr($token_status_color); ?>;">
                    <?php echo number_format($total_tokens); ?>
                </div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">
                    <?php _e('Total Tokens Used', 'kaichat'); ?>
                </div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 6px;">
                <div style="font-size: 28px; font-weight: bold; color: #2271b1;">
                    <?php echo number_format($remaining_tokens); ?>
                </div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">
                    <?php _e('Tokens Remaining', 'kaichat'); ?>
                </div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 6px;">
                <div style="font-size: 28px; font-weight: bold; color: #646970;">
                    <?php echo number_format($token_limit); ?>
                </div>
                <div style="color: #646970; font-size: 13px; margin-top: 5px;">
                    <?php _e('Token Limit', 'kaichat'); ?>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div style="margin: 20px 0;">
            <div style="background: #f0f0f1; height: 30px; border-radius: 15px; overflow: hidden; position: relative;">
                <div style="background: <?php echo esc_attr($token_status_color); ?>; height: 100%; width: <?php echo min(100, $token_percentage); ?>%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center;">
                    <span style="color: white; font-weight: bold; font-size: 12px; position: absolute; left: 50%; transform: translateX(-50%);">
                        <?php echo number_format($token_percentage, 1); ?>% <?php _e('Used', 'kaichat'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($token_percentage >= 90): ?>
        <div class="notice notice-warning inline" style="margin: 15px 0;">
            <p>
                <strong><?php _e('Warning:', 'kaichat'); ?></strong>
                <?php _e('You are approaching your token limit. Please add your own Google Gemini API key to continue using the chatbot without interruption.', 'kaichat'); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <?php if ($total_tokens >= $token_limit): ?>
        <div class="notice notice-error inline" style="margin: 15px 0;">
            <p>
                <strong><?php _e('Token Limit Reached!', 'kaichat'); ?></strong>
                <?php _e('The chatbot is now disabled. Please add your own Google Gemini API key below to continue using the chatbot. The token counter will automatically reset when you add a new API key.', 'kaichat'); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('kcg_ai_chatbot_settings_action', 'kcg_ai_chatbot_settings_nonce'); ?>
        
        <h2><?php _e('General Settings', 'kaichat'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_enabled"><?php _e('Enable Chatbot', 'kaichat'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="kcg_ai_chatbot_enabled" 
                           id="kcg_ai_chatbot_enabled" 
                           value="1" 
                           <?php checked($enabled, 1); ?>>
                    <p class="description"><?php _e('Enable or disable the AI chatbot on your website.', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_api_key"><?php _e('Google Gemini API Key', 'kaichat'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           name="kcg_ai_chatbot_api_key" 
                           id="kcg_ai_chatbot_api_key" 
                           value="<?php echo esc_attr($api_key); ?>" 
                           class="regular-text"
                           placeholder="AIza...">
                    <p class="description">
                        <?php _e('Enter your Google Gemini API key. Get it from', 'kaichat'); ?>
                        <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        <br>
                        <strong><?php _e('Note:', 'kaichat'); ?></strong>
                        <?php _e('Adding your own API key will remove the 10,000 token limit.', 'kaichat'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_model"><?php _e('AI Model', 'kaichat'); ?></label>
                </th>
                <td>
                    <select name="kcg_ai_chatbot_model" id="kcg_ai_chatbot_model">
                        <option value="gemini-2.5-pro" <?php selected($model, 'gemini-2.5-pro'); ?>>Gemini 2.5 Pro</option>
                        <option value="gemini-2.5-flash" <?php selected($model, 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash</option>
                        <option value="gemini-2.5-flash-lite" <?php selected($model, 'gemini-2.5-flash-lite'); ?>>Gemini 2.5 Flash Lite</option>
                    </select>
                    <p class="description"><?php _e('Select the Gemini model to use for responses.', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_max_tokens"><?php _e('Max Tokens', 'kaichat'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           name="kcg_ai_chatbot_max_tokens" 
                           id="kcg_ai_chatbot_max_tokens" 
                           value="<?php echo esc_attr($max_tokens); ?>" 
                           min="50" 
                           max="8192" 
                           step="50">
                    <p class="description"><?php _e('Maximum number of tokens for AI responses (50-8192).', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_temperature"><?php _e('Temperature', 'kaichat'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           name="kcg_ai_chatbot_temperature" 
                           id="kcg_ai_chatbot_temperature" 
                           value="<?php echo esc_attr($temperature); ?>" 
                           min="0" 
                           max="1" 
                           step="0.1">
                    <p class="description"><?php _e('Control randomness: 0 is focused, 1 is creative (0-1).', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_welcome_message"><?php _e('Welcome Message', 'kaichat'); ?></label>
                </th>
                <td>
                    <textarea name="kcg_ai_chatbot_welcome_message" 
                              id="kcg_ai_chatbot_welcome_message" 
                              rows="3" 
                              class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                    <p class="description"><?php _e('The first message users see when opening the chatbot.', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_instructions"><?php _e('Custom Instructions', 'kaichat'); ?></label>
                </th>
                <td>
                    <textarea name="kcg_ai_chatbot_instructions" 
                              id="kcg_ai_chatbot_instructions" 
                              rows="5" 
                              class="large-text"
                              placeholder="<?php esc_attr_e('e.g., You are a customer support agent. Always be polite and helpful. Focus on our products and services.', 'kaichat'); ?>"><?php echo esc_textarea($instructions); ?></textarea>
                    <p class="description"><?php _e('Custom instructions for the AI to follow when responding to users.', 'kaichat'); ?></p>
                </td>
            </tr>
        </table>
        
        <hr>
        
        <h2><?php _e('Knowledge Base Settings', 'kaichat'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Knowledge Base Options', 'kaichat'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="kcg_use_knowledge_base" value="1" 
                                <?php checked(get_option('kcg_use_knowledge_base', 1)); ?>>
                            <?php _e('Use knowledge base for chat responses', 'kaichat'); ?>
                        </label><br>
                        
                        <label style="margin-top: 10px; display: inline-block;">
                            <?php _e('Chunk Size (words):', 'kaichat'); ?>
                            <input type="number" name="kcg_chunk_size" value="<?php echo esc_attr(get_option('kcg_chunk_size', 500)); ?>" 
                                min="100" max="2000" step="50" style="width: 100px;">
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <hr>
        
        <h2><?php _e('Display Settings', 'kaichat'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php _e('Display On', 'kaichat'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" name="kcg_show_on_homepage" value="1" <?php checked(get_option('kcg_show_on_homepage', 1)); ?>> <?php _e('Homepage', 'kaichat'); ?></label><br>
                        <label><input type="checkbox" name="kcg_show_on_posts" value="1" <?php checked(get_option('kcg_show_on_posts', 1)); ?>> <?php _e('Posts', 'kaichat'); ?></label><br>
                        <label><input type="checkbox" name="kcg_show_on_pages" value="1" <?php checked(get_option('kcg_show_on_pages', 1)); ?>> <?php _e('Pages', 'kaichat'); ?></label><br>
                        <label><input type="checkbox" name="kcg_show_on_archives" value="1" <?php checked(get_option('kcg_show_on_archives', 1)); ?>> <?php _e('Archives', 'kaichat'); ?></label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'kaichat'), 'primary', 'kcg_ai_chatbot_save_settings'); ?>
    </form>
    
    <hr>
    <h2><?php _e('Test API Connection', 'kaichat'); ?></h2>
    <p>
        <button type="button" class="button button-secondary" id="test-api-connection">
            <?php _e('Test Gemini Connection', 'kaichat'); ?>
        </button>
        <span id="test-result" style="margin-left: 10px;"></span>
    </p>
</div>