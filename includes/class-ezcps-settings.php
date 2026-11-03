<?php

/**
 * Easy ContentPush - Settings Module
 * Prefix: ezcps_
 */

if (! defined('ABSPATH')) {
    exit;
}

class EZCPS_Settings
{

    private $option_key = 'ezcps_settings';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'ezcps_add_settings_page']);
        add_action('admin_init', [$this, 'ezcps_register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'ezcps_enqueue_settings_assets']);
        add_action('admin_notices', [$this, 'ezcps_admin_notice_receiver_plugin']);
        add_action('admin_notices', [$this, 'ezcps_admin_notice_configuration']);
        add_action('wp_ajax_ezcps_generate_accept_key', [$this, 'ezcps_ajax_generate_accept_key']);
        add_action('wp_ajax_ezcps_test_connection', [$this, 'ezcps_ajax_test_connection']);
    }

    public function ezcps_add_settings_page()
    {
        add_options_page(
            esc_html__('Easy ContentPush Settings', 'easy-content-push'),
            esc_html__('Easy ContentPush', 'easy-content-push'),
            'manage_options',
            'ezcps-settings',
            [$this, 'render_settings_page']
        );
    }

    public function ezcps_enqueue_settings_assets($hook_suffix)
    {
        if ($hook_suffix !== 'settings_page_ezcps-settings') {
            return;
        }

        wp_enqueue_style(
            'ezcps-admin-settings',
            EZCPS_PLUGIN_URL . 'assets/css/ezcps-admin-settings.css',
            [],
            EZCPS_VERSION
        );

        wp_enqueue_script(
            'ezcps-settings',
            EZCPS_PLUGIN_URL . 'assets/js/ezcps-settings.js',
            ['jquery'],
            EZCPS_VERSION,
            true
        );

        wp_localize_script(
            'ezcps-settings',
            'ezcpsSettings',
            [
                'ajaxUrl'             => admin_url('admin-ajax.php'),
                'nonce'               => wp_create_nonce('ezcps_settings'),
                'optionKey'           => $this->option_key,
                'originPlaceholder'   => 'https://',
                'removeOriginLabel'   => esc_html__('Remove', 'easy-content-push'),
                'showKeyLabel'        => esc_html__('Show', 'easy-content-push'),
                'hideKeyLabel'        => esc_html__('Hide', 'easy-content-push'),
                'regenerateConfirm'   => esc_html__('Generate a new receive key? Senders must update their push key after you save.', 'easy-content-push'),
                'keyGenerated'        => esc_html__('New receive key generated. Save settings if you also changed other fields, and share this key with senders.', 'easy-content-push'),
                'copyKeyEmpty'        => esc_html__('Generate or enter a receive key first.', 'easy-content-push'),
                'testing'             => esc_html__('Testing connection…', 'easy-content-push'),
                'genericError'        => esc_html__('Something went wrong. Please try again.', 'easy-content-push'),
            ]
        );
    }

    public function ezcps_register_settings()
    {
        register_setting('ezcps_settings_group', $this->option_key, [$this, 'sanitize_settings']);

        add_settings_section(
            'ezcps_push_section',
            __('Push content to another site', 'easy-content-push'),
            [$this, 'render_push_section_description'],
            'ezcps-settings'
        );
        add_settings_field(
            'ezcps_push_card',
            '',
            [$this, 'render_push_card'],
            'ezcps-settings',
            'ezcps_push_section'
        );

        add_settings_section(
            'ezcps_receive_section',
            __('Receive content from other sites', 'easy-content-push'),
            [$this, 'render_receive_section_description'],
            'ezcps-settings'
        );
        add_settings_field(
            'ezcps_receive_card',
            '',
            [$this, 'render_receive_card'],
            'ezcps-settings',
            'ezcps_receive_section'
        );

        add_settings_section(
            'ezcps_content_section',
            __('Content', 'easy-content-push'),
            [$this, 'render_content_section_description'],
            'ezcps-settings'
        );
        add_settings_field(
            'post_types',
            __('Post types enabled for Push content', 'easy-content-push'),
            [$this, 'render_post_types_multiselect'],
            'ezcps-settings',
            'ezcps_content_section',
            ['label_for' => 'post_types']
        );
    }

    public function render_push_section_description()
    {
        echo '<p class="description">' . esc_html__(
            'Configure where this site sends content. Leave blank if this site only receives content.',
            'easy-content-push'
        ) . '</p>';
    }

    public function render_receive_section_description()
    {
        echo '<p class="description">' . esc_html__(
            'Configure which sites may send content here. Give each sender the same receive key to use as their push key.',
            'easy-content-push'
        ) . '</p>';
    }

    public function render_content_section_description()
    {
        echo '<p class="description">' . esc_html__(
            'Choose which post types show the Push content control in the editor.',
            'easy-content-push'
        ) . '</p>';
    }

    public function sanitize_settings($input)
    {
        if (! is_array($input)) {
            $input = [];
        }

        $existing = get_option($this->option_key, []);
        if (! is_array($existing)) {
            $existing = [];
        }

        $origins = [];
        if (isset($input['allowed_origins']) && is_array($input['allowed_origins'])) {
            foreach ($input['allowed_origins'] as $url) {
                $normalized = ezcps_normalize_site_url($url);
                if ($normalized !== '') {
                    $origins[] = $normalized;
                }
            }
        }
        $origins = array_values(array_unique($origins));

        $push_key = $this->ezcps_sanitize_sync_key($input['push_key'] ?? '');
        if ($push_key === '') {
            $push_key = $this->ezcps_sanitize_sync_key($existing['push_key'] ?? '');
        }

        $accept_key = $this->ezcps_sanitize_sync_key($input['accept_key'] ?? '');
        if ($accept_key === '') {
            $accept_key = $this->ezcps_sanitize_sync_key($existing['accept_key'] ?? '');
        }

        return [
            'prod_url'         => esc_url_raw($input['prod_url'] ?? ''),
            'push_key'         => $push_key,
            'allowed_origins'  => $origins,
            'accept_key'       => $accept_key,
            'post_types'       => array_values(array_filter(array_map('sanitize_key', $input['post_types'] ?? []))),
        ];
    }

    /**
     * @param mixed $key Raw key from form or option.
     * @return string
     */
    private function ezcps_sanitize_sync_key($key)
    {
        $key = is_string($key) ? trim($key) : '';
        if ($key === '') {
            return '';
        }

        return preg_replace('/[^\x20-\x7E]/', '', $key);
    }

    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap ezcps-settings">
            <h1><?php esc_html_e('Easy ContentPush Settings', 'easy-content-push'); ?></h1>
            <p class="ezcps-settings-intro">
                <?php esc_html_e('Install this plugin on each site in your workflow. Use the push and receive sections on any site that sends or receives content.', 'easy-content-push'); ?>
            </p>
            <form method="post" action="options.php">
                <?php
                settings_fields('ezcps_settings_group');
                do_settings_sections('ezcps-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_push_card()
    {
        $options  = get_option($this->option_key, []);
        $prod_url = isset($options['prod_url']) ? esc_attr($options['prod_url']) : '';
        $has_push = $this->ezcps_sanitize_sync_key($options['push_key'] ?? '') !== '';
        ?>
        <div class="ezcps-settings-card" id="ezcps-push">
            <div class="ezcps-field">
                <label for="ezcps_prod_url"><?php esc_html_e('Target site URL', 'easy-content-push'); ?></label>
                <input type="url" class="regular-text code" id="ezcps_prod_url"
                    name="<?php echo esc_attr($this->option_key . '[prod_url]'); ?>"
                    value="<?php echo esc_attr($prod_url); ?>"
                    placeholder="https://example.com" />
                <p class="description"><?php esc_html_e('The site that should receive content when you click Push content.', 'easy-content-push'); ?></p>
            </div>
            <div class="ezcps-field">
                <label for="ezcps_push_key"><?php esc_html_e('Push key', 'easy-content-push'); ?></label>
                <div class="ezcps-key-row">
                    <input type="password" class="regular-text ezcps-key-input" id="ezcps_push_key"
                        name="<?php echo esc_attr($this->option_key . '[push_key]'); ?>"
                        value=""
                        autocomplete="new-password"
                        placeholder="<?php echo esc_attr($has_push ? '********' : ''); ?>" />
                    <button type="button" class="button ezcps-toggle-key" data-target="#ezcps_push_key"><?php esc_html_e('Show', 'easy-content-push'); ?></button>
                </div>
                <p class="description"><?php esc_html_e('Paste the receive key from the target site. Leave blank to keep the current key.', 'easy-content-push'); ?></p>
            </div>
            <div class="ezcps-field">
                <button type="button" class="button button-secondary ezcps-test-connection"><?php esc_html_e('Test connection', 'easy-content-push'); ?></button>
                <p class="description"><?php esc_html_e('Uses saved target URL and push key. Save changes first if you edited them above.', 'easy-content-push'); ?></p>
                <p class="ezcps-test-result" aria-live="polite"></p>
            </div>
        </div>
        <?php
    }

    public function render_receive_card()
    {
        $options = get_option($this->option_key, []);
        $origins = isset($options['allowed_origins']) && is_array($options['allowed_origins']) ? $options['allowed_origins'] : [];
        if (empty($origins)) {
            $origins = [''];
        }
        $has_accept = $this->ezcps_sanitize_sync_key($options['accept_key'] ?? '') !== '';
        ?>
        <div class="ezcps-settings-card" id="ezcps-receive">
            <div class="ezcps-field ezcps-origin-list">
                <label><?php esc_html_e('Sender site URLs', 'easy-content-push'); ?></label>
                <div class="ezcps-origin-rows">
                    <?php foreach ($origins as $index => $origin) : ?>
                        <div class="ezcps-origin-row">
                            <input type="url" class="regular-text code"
                                name="<?php echo esc_attr($this->option_key . '[allowed_origins][' . (int) $index . ']'); ?>"
                                value="<?php echo esc_attr($origin); ?>"
                                placeholder="https://" />
                            <button type="button" class="button ezcps-remove-origin"><?php esc_html_e('Remove', 'easy-content-push'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p>
                    <button type="button" class="button ezcps-add-origin"><?php esc_html_e('Add sender site', 'easy-content-push'); ?></button>
                </p>
                <p class="description"><?php esc_html_e('WordPress site addresses allowed to send content here (must match Settings → General on each sender site).', 'easy-content-push'); ?></p>
            </div>
            <div class="ezcps-divider"></div>
            <div class="ezcps-field">
                <label for="ezcps_accept_key"><?php esc_html_e('Receive key', 'easy-content-push'); ?></label>
                <div class="ezcps-key-row">
                    <input type="password" class="regular-text ezcps-key-input" id="ezcps_accept_key"
                        name="<?php echo esc_attr($this->option_key . '[accept_key]'); ?>"
                        value=""
                        autocomplete="new-password"
                        placeholder="<?php echo esc_attr($has_accept ? '********' : ''); ?>" />
                    <button type="button" class="button ezcps-toggle-key" data-target="#ezcps_accept_key"><?php esc_html_e('Show', 'easy-content-push'); ?></button>
                </div>
                <p class="ezcps-key-row">
                    <button type="button" class="button button-secondary ezcps-generate-accept-key"><?php esc_html_e('Generate new key', 'easy-content-push'); ?></button>
                    <button type="button" class="button button-secondary ezcps-copy-accept-key"><?php esc_html_e('Copy key', 'easy-content-push'); ?></button>
                </p>
                <p class="description ezcps-accept-key-status" aria-live="polite"><?php esc_html_e('Share this key with each sender site as their push key. Leave blank to keep the current key when saving.', 'easy-content-push'); ?></p>
            </div>
        </div>
        <?php
    }

    public function render_post_types_multiselect($args)
    {
        $options  = get_option($this->option_key, []);
        $selected = $options['post_types'] ?? [];

        $post_types = get_post_types(['show_ui' => true], 'objects');
        ?>
        <div class="ezcps-settings-card">
            <select id="<?php echo esc_attr($args['label_for']); ?>" class="ezcps-chosen" name="<?php echo esc_attr($this->option_key . '[post_types][]'); ?>" multiple="multiple" style="width: 100%; max-width: 640px;">
                <?php foreach ($post_types as $type) : ?>
                    <option value="<?php echo esc_attr($type->name); ?>" <?php selected(in_array($type->name, $selected, true)); ?>><?php echo esc_html($type->label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function ezcps_ajax_generate_accept_key()
    {
        check_ajax_referer('ezcps_settings', 'security');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission.', 'easy-content-push')]);
        }

        $key     = ezcps_generate_sync_key();
        $options = get_option($this->option_key, []);
        if (! is_array($options)) {
            $options = [];
        }
        $options['accept_key'] = $key;
        update_option($this->option_key, $options, false);

        wp_send_json_success(['key' => $key]);
    }

    public function ezcps_ajax_test_connection()
    {
        check_ajax_referer('ezcps_settings', 'security');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission.', 'easy-content-push')]);
        }

        $prod_url = self::get_option('prod_url');
        $push_key = self::get_sync_key('push_key');

        if (empty($prod_url)) {
            wp_send_json_error(['message' => __('Target site URL is not set.', 'easy-content-push')]);
        }
        if ($push_key === '') {
            wp_send_json_error(['message' => __('Push key is not set.', 'easy-content-push')]);
        }

        $body     = ezcps_get_ping_request_body();
        $response = wp_remote_post(
            trailingslashit($prod_url) . 'wp-json/ezcps-sync/v1/ping',
            [
                'headers' => ezcps_build_sync_request_headers($body, $push_key),
                'body'    => $body,
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            wp_send_json_error([
                'message' => sprintf(
                    /* translators: %d: HTTP status code */
                    __('Connection failed (HTTP %d). Check the target URL, push key, and that this site\'s URL is listed under sender sites on the target.', 'easy-content-push'),
                    $code
                ),
            ]);
        }

        wp_send_json_success(['message' => __('Connection successful.', 'easy-content-push')]);
    }

    public static function get_option($key, $default = null)
    {
        $options = get_option('ezcps_settings', []);
        if (! is_array($options) || ! array_key_exists($key, $options)) {
            return $default;
        }

        $value = $options[$key];
        if ($key === 'allowed_origins' || $key === 'post_types') {
            return is_array($value) ? $value : ($default ?? []);
        }

        if (is_string($value) && $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @param string $key Option key push_key or accept_key.
     * @return string
     */
    public static function get_sync_key($key)
    {
        $options = get_option('ezcps_settings', []);
        if (! is_array($options) || ! isset($options[$key])) {
            return '';
        }

        return is_string($options[$key]) ? $options[$key] : '';
    }

    /**
     * @return string[]
     */
    public static function get_allowed_origins()
    {
        $origins = self::get_option('allowed_origins', []);
        return is_array($origins) ? $origins : [];
    }

    public function ezcps_admin_notice_receiver_plugin()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'settings_page_ezcps-settings') {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible"><p><strong>Easy ContentPush:</strong> ' . esc_html__(
            'Install and activate this plugin on each site that sends or receives content.',
            'easy-content-push'
        ) . '</p></div>';
    }

    public function ezcps_admin_notice_configuration()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'settings_page_ezcps-settings') {
            return;
        }

        $prod_url = self::get_option('prod_url');
        if (! empty($prod_url) && self::get_sync_key('push_key') === '') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Easy ContentPush:</strong> ' . esc_html__(
                'Target site URL is set but the push key is missing. Pushes will fail until you add the receive key from the target site.',
                'easy-content-push'
            ) . '</p></div>';
        }

        $origins = self::get_allowed_origins();
        if (! empty($origins) && self::get_sync_key('accept_key') === '') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Easy ContentPush:</strong> ' . esc_html__(
                'Sender sites are configured but the receive key is missing. Incoming content will be rejected.',
                'easy-content-push'
            ) . '</p></div>';
        }
    }
}
