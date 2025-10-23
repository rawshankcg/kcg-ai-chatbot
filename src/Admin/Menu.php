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
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('KCG AI Chatbot', 'kcg-ai-chatbot'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=kcg-ai-chatbot&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="?page=kcg-ai-chatbot&tab=knowledge" class="nav-tab <?php echo $active_tab === 'knowledge' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Knowledge Base', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="?page=kcg-ai-chatbot&tab=conversations" class="nav-tab <?php echo $active_tab === 'conversations' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Conversations', 'kcg-ai-chatbot'); ?>
                </a>
                <a href="?page=kcg-ai-chatbot&tab=design" class="nav-tab <?php echo $active_tab === 'design' ? 'nav-tab-active' : ''; ?>">
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
