<?php
if (!defined('ABSPATH')) {
    exit;
}

class KCG_AI_Chatbot_Menu {
    
    private static $instance = null;
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_menu() {
        add_menu_page(
            __('KCG AI Chatbot', 'kcg-ai-chatbot'),
            __('KCG Chatbot', 'kcg-ai-chatbot'),
            'manage_options',
            'kcg-ai-chatbot',
            array($this, 'render_main_page'),
            'dashicons-format-chat',
            30
        );
    }

    public function render_main_page() {
        // Default tab
        $active_tab = 'settings';
        
        // Check for tab parameter with nonce verification
        if (isset($_GET['tab'])) {
            // Verify nonce for tab switching
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'kcg_switch_tab')) {
                $active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
            } else {
                // If nonce is not set or invalid, use default tab
                $active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
            }
        }
        
        // Validate tab value
        $valid_tabs = array('settings', 'knowledge', 'conversations', 'design');
        if (!in_array($active_tab, $valid_tabs, true)) {
            $active_tab = 'settings';
        }
        
        // Generate nonce for tab links
        $tab_nonce = wp_create_nonce('kcg_switch_tab');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('KCG AI Chatbot', 'kcg-ai-chatbot'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'kcg-ai-chatbot', 'tab' => 'settings', '_wpnonce' => $tab_nonce), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'kcg-ai-chatbot', 'tab' => 'knowledge', '_wpnonce' => $tab_nonce), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'knowledge' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Knowledge Base', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'kcg-ai-chatbot', 'tab' => 'conversations', '_wpnonce' => $tab_nonce), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'conversations' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Conversations', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'kcg-ai-chatbot', 'tab' => 'design', '_wpnonce' => $tab_nonce), admin_url('admin.php'))); ?>" 
                   class="nav-tab <?php echo $active_tab === 'design' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Chatbot Design', 'kcg-ai-chatbot'); ?>
                </a>
            </h2>

            <div class="kcg-chatbot-tab-content">
                <?php
                switch ($active_tab) {
                    case 'design':
                        require_once KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/admin/design-page.php';
                        break;
                    case 'knowledge':
                        require_once KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/admin/knowledge-page.php';
                        break;
                    case 'conversations':
                        require_once KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/admin/conversations-page.php';
                        break;
                    default:
                        require_once KCG_AI_CHATBOT_PLUGIN_DIR . 'templates/admin/settings-page.php';
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

}

function KCG_AI_Chatbot_Menu() {
    return KCG_AI_Chatbot_Menu::get_instance();
}

KCG_AI_Chatbot_Menu();