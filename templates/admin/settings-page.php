<?php 
if (!defined('ABSPATH')) {
    exit;
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
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'kaichat') . '</p></div>';
}

$enabled = get_option('kcg_ai_chatbot_enabled', true);
$api_key = get_option('kcg_ai_chatbot_api_key', '');
$model = get_option('kcg_ai_chatbot_model', 'gpt-4');
$max_tokens = get_option('kcg_ai_chatbot_max_tokens', 500);
$temperature = get_option('kcg_ai_chatbot_temperature', 0.7);
$welcome_message = get_option('kcg_ai_chatbot_welcome_message', 'Hello! How can I help you today?');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('kcg_ai_chatbot_settings_action', 'kcg_ai_chatbot_settings_nonce'); ?>
        
        <table class="form-table">
            <!-- Enable/Disable Chatbot -->
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
            
            <!-- API Key -->
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_api_key"><?php _e('OpenAI API Key', 'kaichat'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           name="kcg_ai_chatbot_api_key" 
                           id="kcg_ai_chatbot_api_key" 
                           value="<?php echo esc_attr($api_key); ?>" 
                           class="regular-text"
                           placeholder="sk-...">
                    <p class="description">
                        <?php _e('Enter your OpenAI API key. Get it from', 'kaichat'); ?>
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                    </p>
                </td>
            </tr>
            
            <!-- Model Selection -->
            <tr>
                <th scope="row">
                    <label for="kcg_ai_chatbot_model"><?php _e('AI Model', 'kaichat'); ?></label>
                </th>
                <td>
                    <select name="kcg_ai_chatbot_model" id="kcg_ai_chatbot_model">
                        <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                        <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                        <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                    </select>
                    <p class="description"><?php _e('Select the AI model to use for responses.', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <!-- Max Tokens -->
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
                           max="4000" 
                           step="50">
                    <p class="description"><?php _e('Maximum number of tokens for AI responses (50-4000).', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <!-- Temperature -->
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
                           max="2" 
                           step="0.1">
                    <p class="description"><?php _e('Control randomness: 0 is focused, 2 is creative (0-2).', 'kaichat'); ?></p>
                </td>
            </tr>
            
            <!-- Welcome Message -->
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
        </table>
        
        <?php submit_button(__('Save Settings', 'kaichat'), 'primary', 'kcg_ai_chatbot_save_settings'); ?>
    </form>
    
    <!-- Test Connection Section -->
    <hr>
    <h2><?php _e('Test API Connection', 'kaichat'); ?></h2>
    <p>
        <button type="button" class="button button-secondary" id="test-api-connection">
            <?php _e('Test Connection', 'kaichat'); ?>
        </button>
        <span id="test-result" style="margin-left: 10px;"></span>
    </p>
</div>