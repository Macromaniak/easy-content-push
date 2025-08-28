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
        add_action('admin_notices', [$this, 'ezcps_admin_notice_receiver_plugin']);
        add_action('admin_notices', [$this, 'ezcps_admin_notice_missing_prod_url']);
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

    public function ezcps_register_settings()
    {
        register_setting('ezcps_settings_group', $this->option_key, [$this, 'sanitize_settings']);

        add_settings_section('ezcps_main_section', __('Configuration', 'easy-content-push'), null, 'ezcps-settings');

        add_settings_field(
            'prod_url',
            __('Target Site URL (to push content to)', 'easy-content-push'),
            [$this, 'render_text_field'],
            'ezcps-settings',
            'ezcps_main_section',
            ['label_for' => 'prod_url', 'placeholder' => '']
        );
        add_settings_field(
            'dev_url',
            __('Origin Site URL (to receive content from)', 'easy-content-push'),
            [$this, 'render_text_field'],
            'ezcps-settings',
            'ezcps_main_section',
            ['label_for' => 'dev_url', 'placeholder' => '']
        );
        add_settings_field(
            'post_types',
            __('Post Types to Allow Push', 'easy-content-push'),
            [$this, 'render_post_types_multiselect'],
            'ezcps-settings',
            'ezcps_main_section',
            ['label_for' => 'post_types']
        );
    }

    public function sanitize_settings($input)
    {
        return [
            'prod_url'   => esc_url_raw($input['prod_url'] ?? ''),
            'dev_url'    => esc_url_raw($input['dev_url'] ?? ''),
            'post_types' => array_filter(array_map('sanitize_key', $input['post_types'] ?? [])),
        ];
    }

    public function render_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Easy ContentPush Settings', 'easy-content-push'); ?></h1>
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

    public function render_text_field($args)
    {
        $options = get_option($this->option_key);
        $id = $args['label_for'];
        $value = esc_attr($options[$id] ?? '');
        echo "<input type='text' id='" . esc_attr($id) . "' name='" . esc_attr($this->option_key . '[' . $id . ']') . "' value='" . esc_attr($value) . "' placeholder='" . esc_attr($args['placeholder']) . "' class='regular-text' />";
    }

    public function render_post_types_multiselect($args)
    {
        $options = get_option($this->option_key);
        $selected = $options['post_types'] ?? [];

        $post_types = get_post_types(['show_ui' => true], 'objects');
        echo "<select id='" . esc_attr('post_types') . "' class='ezcps-chosen' name='" . esc_attr($this->option_key) . "[post_types][]' multiple='multiple' style='width: 300px; max-width: 100%;'>";
        foreach ($post_types as $type) {
            $is_selected = in_array($type->name, $selected) ? 'selected' : '';
            echo "<option value='" . esc_attr($type->name) . "' {$is_selected}>" . esc_html($type->label) . "</option>";
        }
        echo "</select>";
    }

    public static function get_option($key, $default = null)
    {
        $options = get_option('ezcps_settings', []);
        return isset($options[$key]) && ! empty($options[$key]) ? $options[$key] : $default;
    }

    public function ezcps_admin_notice_receiver_plugin()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Easy ContentPush:</strong> ' . esc_html__('Make sure the Easy ContentPush plugin is installed and activated on the other website as well for this to work.', 'easy-content-push') . '</p>
        </div>';
    }

    public function ezcps_admin_notice_missing_prod_url()
    {
        if (! current_user_can('manage_options')) return;
        $prod_url = EZCPS_Settings::get_option('prod_url');
        if (empty($prod_url)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Easy ContentPush:</strong> ' . esc_html__('Target Site URL is not set. You cannot send content without setting it.', 'easy-content-push') . '</p></div>';
        }

        $dev_url = EZCPS_Settings::get_option('dev_url');
        if (empty($dev_url)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Easy ContentPush:</strong> ' . esc_html__('Origin Site URL is not set. You cannot receive content without setting it.', 'easy-content-push') . '</p></div>';
        }
    }
}
