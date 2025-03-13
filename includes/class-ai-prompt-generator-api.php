<?php
/**
 * API integration for AI Prompt Generator
 */
class AI_Prompt_Generator_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Constructor
     */
    public function __construct() {
        // Set the API endpoint based on the selected model
        $model = get_option('ai_prompt_generator_model', 'gpt-3.5-turbo');

        if (strpos($model, 'claude') !== false) {
            $this->api_endpoint = 'https://api.anthropic.com/v1/messages';
        }
    }

    /**
     * Generate content using AI API
     */
    public function generate_content($prompt_type, $prompt_topic, $prompt_keywords, $content_length, $tone, $custom_prompt = '') {
        $api_key = get_option('ai_prompt_generator_api_key', '');
        $model = get_option('ai_prompt_generator_model', 'gpt-3.5-turbo');

        if (empty($api_key)) {
            return new WP_Error('api_key_missing', __('API key is not set', 'ai-prompt-generator'));
        }

        // Build the prompt
        $prompt = $this->build_prompt($prompt_type, $prompt_topic, $prompt_keywords, $content_length, $tone, $custom_prompt);

        // Make the API request
        $response = $this->make_api_request($prompt, $api_key, $model);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response;
    }

    /**
     * Build the prompt based on parameters
     */
    private function build_prompt($prompt_type, $prompt_topic, $prompt_keywords, $content_length, $tone, $custom_prompt) {
        if ($prompt_type === 'custom' && !empty($custom_prompt)) {
            return $custom_prompt;
        }

        $length_map = [
            'short' => '300-500 words',
            'medium' => '500-800 words',
            'long' => '800-1200 words'
        ];

        $length_text = isset($length_map[$content_length]) ? $length_map[$content_length] : $length_map['medium'];

        $keywords_text = !empty($prompt_keywords) ? " Include the following keywords: $prompt_keywords." : "";

        $prompt_templates = [
            'blog_post' => "Write a $tone blog post about \"$prompt_topic\". The post should be $length_text in length.$keywords_text Include an engaging introduction, 3-5 main points with subheadings, and a conclusion. Format the content with proper headings and paragraphs.",

            'product_description' => "Write a $tone product description for \"$prompt_topic\". The description should be $length_text in length.$keywords_text Highlight the key features, benefits, and use cases. Make it compelling and persuasive.",

            'social_media' => "Create a $tone social media post about \"$prompt_topic\". The post should be concise and engaging.$keywords_text Include relevant hashtags and a call to action.",

            'email' => "Write a $tone email about \"$prompt_topic\". The email should be $length_text in length.$keywords_text Include a subject line, greeting, body with key points, and a clear call to action. Make it engaging and professional."
        ];

        if (isset($prompt_templates[$prompt_type])) {
            return $prompt_templates[$prompt_type];
        }

        // Default to blog post if type not found
        return $prompt_templates['blog_post'];
    }

    /**
     * Make the API request
     */
    private function make_api_request($prompt, $api_key, $model) {
        $headers = [];
        $body = [];

        // Prepare request based on model type
        if (strpos($model, 'claude') !== false) {
            // Claude API
            $headers = [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ];

            $body = [
                'model' => $model,
                'max_tokens' => 4000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ];
        } else {
            // OpenAI API
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ];

            $body = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2048,
                'temperature' => 0.7
            ];
        }

        $response = wp_remote_post(
            $this->api_endpoint,
            [
                'headers' => $headers,
                'body' => json_encode($body),
                'timeout' => 60
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_body($response);
            return new WP_Error('api_error', sprintf(__('API Error: %s', 'ai-prompt-generator'), $error_message));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Extract content based on API response structure
        if (strpos($model, 'claude') !== false) {
            // Claude API response
            if (isset($body['content']) && is_array($body['content']) && !empty($body['content'][0]['text'])) {
                return $body['content'][0]['text'];
            }
        } else {
            // OpenAI API response
            if (isset($body['choices']) && is_array($body['choices']) && !empty($body['choices'][0]['message']['content'])) {
                return $body['choices'][0]['message']['content'];
            }
        }

        return new WP_Error('invalid_response', __('Invalid API response', 'ai-prompt-generator'));
    }
}