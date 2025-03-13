<?php

/**
 * Plugin Name: AI Prompt Generator
 * Plugin URI: https://example.com/plugins/ai-prompt-generator
 * Description: A WordPress plugin that generates content using AI prompts.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: ai-prompt-generator
 * License: GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('AI_PROMPT_GENERATOR_VERSION', '1.0.0');
define('AI_PROMPT_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_PROMPT_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class AI_Prompt_Generator
{
    /**
     * Instance of this class.
     */
    protected static $instance = null;

    /**
     * Initialize the plugin.
     */
    public function __construct()
    {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies()
    {
        // Admin area
        require_once AI_PROMPT_GENERATOR_PLUGIN_DIR . 'admin/class-ai-prompt-generator-admin.php';

        // API integration
        require_once AI_PROMPT_GENERATOR_PLUGIN_DIR . 'includes/class-ai-prompt-generator-api.php';
    }

    /**
     * Register all of the hooks related to the admin area.
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new AI_Prompt_Generator_Admin();

        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
        add_action('admin_menu', array($plugin_admin, 'add_menu_pages'));
        add_action('wp_ajax_generate_ai_content', array($plugin_admin, 'generate_ai_content'));
    }

    /**
     * Return an instance of this class.
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Activation hook.
     */
    public static function activate()
    {
        // Add activation code here
        update_option('ai_prompt_generator_api_key', '');
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate()
    {
        // Add deactivation code here
    }
}

// Admin class
require_once AI_PROMPT_GENERATOR_PLUGIN_DIR . 'admin/class-ai-prompt-generator-admin.php';

/**
 * Begins execution of the plugin.
 */
function run_ai_prompt_generator()
{
    $plugin = AI_Prompt_Generator::get_instance();
}

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('AI_Prompt_Generator', 'activate'));
register_deactivation_hook(__FILE__, array('AI_Prompt_Generator', 'deactivate'));

run_ai_prompt_generator();
