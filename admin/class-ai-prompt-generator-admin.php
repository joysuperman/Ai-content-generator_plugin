<?php

/**
 * The admin-specific functionality of the plugin.
 */
class AI_Prompt_Generator_Admin
{

    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        // Constructor code here
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            'ai-prompt-generator-admin',
            AI_PROMPT_GENERATOR_PLUGIN_URL . 'admin/css/ai-prompt-generator-admin.css',
            array(),
            AI_PROMPT_GENERATOR_VERSION
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'ai-prompt-generator-admin',
            AI_PROMPT_GENERATOR_PLUGIN_URL . 'admin/js/ai-prompt-generator-admin.js',
            array('jquery'),
            AI_PROMPT_GENERATOR_VERSION,
            false
        );

        wp_localize_script(
            'ai-prompt-generator-admin',
            'ai_prompt_generator_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_prompt_generator_nonce')
            )
        );
    }

    /**
     * Add menu pages
     */
    public function add_menu_pages()
    {
        add_menu_page(
            __('Content Generator', 'ai-prompt-generator'),
            __('Content Generator', 'ai-prompt-generator'),
            'manage_options',
            'ai-prompt-generator',
            array($this, 'render_generator_page'),
            'dashicons-text',
            30
        );

        add_submenu_page(
            'ai-prompt-generator',
            __('Settings', 'ai-prompt-generator'),
            __('Settings', 'ai-prompt-generator'),
            'manage_options',
            'ai-prompt-generator-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the generator page
     */
    public function render_generator_page()
    {
?>
        <div class="wrap ai-generator-wrapper">
            <h1><?php echo esc_html__('Content Generator', 'ai-prompt-generator'); ?></h1>

            <div class="ai-prompt-generator-container">
                <div class="ai-prompt-form">
                    <h2><?php echo esc_html__('Generate Content', 'ai-prompt-generator'); ?></h2>

                    <div class="form-group">
                        <label for="prompt_type"><?php echo esc_html__('Content Type', 'ai-prompt-generator'); ?></label>
                        <select id="prompt_type">
                            <option value="blog_post"><?php echo esc_html__('Blog Post', 'ai-prompt-generator'); ?></option>
                            <option value="product_description"><?php echo esc_html__('Product Description', 'ai-prompt-generator'); ?></option>
                            <option value="social_media"><?php echo esc_html__('Social Media Post', 'ai-prompt-generator'); ?></option>
                            <option value="email"><?php echo esc_html__('Email', 'ai-prompt-generator'); ?></option>
                            <option value="custom"><?php echo esc_html__('Custom Prompt', 'ai-prompt-generator'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="prompt_topic"><?php echo esc_html__('Topic or Title', 'ai-prompt-generator'); ?></label>
                        <input type="text" id="prompt_topic" placeholder="<?php echo esc_attr__('Enter your topic or title', 'ai-prompt-generator'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="prompt_keywords"><?php echo esc_html__('Keywords (optional)', 'ai-prompt-generator'); ?></label>
                        <input type="text" id="prompt_keywords" placeholder="<?php echo esc_attr__('Enter keywords separated by commas', 'ai-prompt-generator'); ?>">
                    </div>

                    <div class="form-group custom-prompt-container" style="display: none;">
                        <label for="custom_prompt"><?php echo esc_html__('Custom Prompt', 'ai-prompt-generator'); ?></label>
                        <textarea id="custom_prompt" rows="4" placeholder="<?php echo esc_attr__('Enter your custom AI prompt here', 'ai-prompt-generator'); ?>"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="content_length"><?php echo esc_html__('Content Length', 'ai-prompt-generator'); ?></label>
                        <select id="content_length">
                            <option value="short"><?php echo esc_html__('Short', 'ai-prompt-generator'); ?></option>
                            <option value="medium"><?php echo esc_html__('Medium', 'ai-prompt-generator'); ?></option>
                            <option value="long"><?php echo esc_html__('Long', 'ai-prompt-generator'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tone"><?php echo esc_html__('Tone', 'ai-prompt-generator'); ?></label>
                        <select id="tone">
                            <option value="professional"><?php echo esc_html__('Professional', 'ai-prompt-generator'); ?></option>
                            <option value="casual"><?php echo esc_html__('Casual', 'ai-prompt-generator'); ?></option>
                            <option value="enthusiastic"><?php echo esc_html__('Enthusiastic', 'ai-prompt-generator'); ?></option>
                            <option value="informative"><?php echo esc_html__('Informative', 'ai-prompt-generator'); ?></option>
                            <option value="persuasive"><?php echo esc_html__('Persuasive', 'ai-prompt-generator'); ?></option>
                        </select>
                    </div>

                    <button id="generate_content_btn" class="button button-primary">
                        <?php echo esc_html__('Generate Content', 'ai-prompt-generator'); ?>
                    </button>
                </div>

                <div class="ai-content-results">
                    <div id="content_spinner" style="display: none;">
                        <span class="spinner is-active"></span>
                    </div>
                    <div id="generated_content_wrapper">
                        <h2>Generated Content</h2>
                        <?php
                        wp_editor('', 'generated_content', array(
                            'media_buttons' => true,
                            'textarea_rows' => 10,
                            'teeny' => false,
                            'quicktags' => true,
                            'tinymce' => true
                        ));
                        ?>
                        <div class="content-actions" style="display: none;">
                            <button id="copy_content_btn" class="button">
                                <?php echo esc_html__('Copy Content', 'ai-prompt-generator'); ?>
                            </button>
                            <button id="create_post_btn" class="button button-primary">
                                <?php echo esc_html__('Create Draft Post', 'ai-prompt-generator'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Generate AI content via AJAX
     */
    public function generate_ai_content()
    {
        check_ajax_referer('ai_prompt_generator_nonce', 'nonce');

        $api_key = get_option('ai_prompt_generator_api_key', '');

        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => __('API key is not set. Please configure it in the plugin settings.', 'ai-prompt-generator')
            ));
        }

        $prompt_type = sanitize_text_field($_POST['prompt_type']);
        $prompt_topic = sanitize_text_field($_POST['prompt_topic']);
        $prompt_keywords = sanitize_text_field($_POST['prompt_keywords']);
        $content_length = sanitize_text_field($_POST['content_length']);
        $tone = sanitize_text_field($_POST['tone']);
        $custom_prompt = isset($_POST['custom_prompt']) ? sanitize_textarea_field($_POST['custom_prompt']) : '';

        $api = new AI_Prompt_Generator_API();
        $result = $api->generate_content($prompt_type, $prompt_topic, $prompt_keywords, $content_length, $tone, $custom_prompt);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'content' => $result
        ));
    }

    /**
     * Render the settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings if form is submitted
        if (isset($_POST['submit']) && check_admin_referer('ai_prompt_generator_settings')) {
            update_option('ai_prompt_generator_api_key', sanitize_text_field($_POST['ai_prompt_generator_api_key']));
            update_option('ai_prompt_generator_model', sanitize_text_field($_POST['ai_prompt_generator_model']));
            update_option('ai_prompt_generator_temperature', floatval($_POST['ai_prompt_generator_temperature']));
            add_settings_error('ai_prompt_generator_messages', 'ai_prompt_generator_message', __('Settings Saved', 'ai-prompt-generator'), 'updated');
        }

        // Get current values
        $api_key = get_option('ai_prompt_generator_api_key', '');
        $model = get_option('ai_prompt_generator_model', 'gpt-3.5-turbo');
        $temperature = get_option('ai_prompt_generator_temperature', '0.7');

        settings_errors('ai_prompt_generator_messages');
    ?>
        <div class="wrap ai-prompt-generator-container">
            <div class="ai-prompt-generator-content">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form method="post" action="options.php" class="form-container">
                    <?php
                    settings_fields('ai_prompt_generator_settings');
                    do_settings_sections('ai_prompt_generator_settings');
                    ?>
                    <div class="form-group">
                        <label for="ai_prompt_generator_api_key"><?php echo esc_html__('API Key', 'ai-prompt-generator'); ?></label>
                        <input type="password" id="ai_prompt_generator_api_key" name="ai_prompt_generator_api_key"
                            value="<?php echo esc_attr(get_option('ai_prompt_generator_api_key')); ?>" class="regular-text" />
                        <p class="description"><?php echo esc_html__('Enter your API key for content generation.', 'ai-prompt-generator'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="ai_prompt_generator_model"><?php echo esc_html__('AI Model', 'ai-prompt-generator'); ?></label>
                        <select id="ai_prompt_generator_model" name="ai_prompt_generator_model">
                            <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="claude-3-sonnet" <?php selected($model, 'claude-3-sonnet'); ?>>Claude 3 Sonnet</option>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the AI model to use for content generation.', 'ai-prompt-generator'); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="ai_prompt_generator_temperature"><?php echo esc_html__('Temperature', 'ai-prompt-generator'); ?></label>
                        <div class="range-container">
                            <input type="range" id="ai_prompt_generator_temperature" name="ai_prompt_generator_temperature"
                                min="0" max="2" step="0.1" value="<?php echo esc_attr($temperature); ?>" />
                            <span class="temperature-value"><?php echo esc_html($temperature); ?></span>
                        </div>
                        <p class="description"><?php echo esc_html__('Controls randomness in the output. Lower values make the output more focused and deterministic.', 'ai-prompt-generator'); ?></p>
                    </div>

                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
<?php
    }
}
