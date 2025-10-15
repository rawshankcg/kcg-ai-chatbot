<?php 
/*
 * Plugin Name:       KCG AI Chatbot
 * Plugin URI:        https://kingscrestglobal.com/
 * Description:       KCG AI Chatbot adds an intelligent, customizable AI assistant to your site for instant answers, support, and user engagement.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            KCG
 * Author URI:        https://kingscrestglobal.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       kaichat
 * Domain Path:       /languages
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class KCG_AI_Chatbot {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    private function define_constants() {
        define('KCG_AI_CHATBOT_VERSION', '1.0.0');
        define('KCG_AI_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('KCG_AI_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('KCG_AI_CHATBOT_PLUGIN_FILE', __FILE__);
    }

    private function includes() {
        $directories = [
            'Core',
            'Admin',
            'Frontend',
            'API',
            'AI',
            'Database',
            'Services',
        ];
        
        foreach ($directories as $dir) {
            $this->load_directory_files('src/' . $dir);
        }
    }

    private function load_directory_files($directory) {
        $dir_path = KCG_AI_CHATBOT_PLUGIN_DIR . $directory;
        
        if (!is_dir($dir_path)) {
            return;
        }
        
        $files = glob($dir_path . '/*.php');
        
        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file) && is_readable($file)) {
                    require_once $file;
                }
            }
        }
        
        $subdirs = glob($dir_path . '/*', GLOB_ONLYDIR);
        if (!empty($subdirs)) {
            foreach ($subdirs as $subdir) {
                $relative_path = str_replace(KCG_AI_CHATBOT_PLUGIN_DIR, '', $subdir);
                $this->load_directory_files($relative_path);
            }
        }
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array('KCG_AI_Chatbot_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('KCG_AI_Chatbot_Deactivator', 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('kaichat', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

function kcg_ai_chatbot() {
    return KCG_AI_Chatbot::get_instance();
}

kcg_ai_chatbot();