<?php
namespace KHFormBuilder\Shortcodes;

class FormShortcode
{
    public static function register()
    {
        add_shortcode('kh_form', [__CLASS__, 'render']);
    }

    public static function render($atts)
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'kh_form');

        $formId = absint($atts['id']);
        if (! $formId) {
            return '';
        }

        $post = get_post($formId);
        if (! $post || 'kh_form' !== $post->post_type) {
            return '';
        }

        $fields = get_post_meta($formId, '_kh_form_fields', true);
        $fields = is_array($fields) ? $fields : [];

        wp_enqueue_style('kh-form-builder-frontend', KH_FORM_BUILDER_URL . 'assets/css/frontend.css', [], KH_FORM_BUILDER_VERSION);
        wp_enqueue_script('kh-form-builder-frontend', KH_FORM_BUILDER_URL . 'assets/js/frontend.js', ['jquery'], KH_FORM_BUILDER_VERSION, true);
        wp_localize_script('kh-form-builder-frontend', 'khFormFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'error'   => __('Something went wrong. Please try again.', 'kh-form-builder'),
        ]);

        ob_start();
        ?>
        <form class="kh-form" method="post" data-form-id="<?php echo esc_attr($formId); ?>">
            <?php foreach ($fields as $field) :
                $type = isset($field['type']) ? $field['type'] : 'text';
                $fieldId = 'kh_form_' . $formId . '_' . sanitize_title($field['label']);
                ?>
                <div class="kh-form-field">
                    <label for="<?php echo esc_attr($fieldId); ?>"><?php echo esc_html($field['label']); ?><?php echo ! empty($field['required']) ? ' *' : ''; ?></label>
                    <?php echo self::renderFieldInput($field, $fieldId); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endforeach; ?>
            <?php wp_nonce_field('kh_form_submit_' . $formId); ?>
            <input type="hidden" name="kh_form_id" value="<?php echo esc_attr($formId); ?>" />
            <button type="submit" class="kh-form-submit"><?php esc_html_e('Send', 'kh-form-builder'); ?></button>
            <div class="kh-form-response" style="display:none;"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    private static function renderFieldInput($field, $fieldId)
    {
        $type     = isset($field['type']) ? $field['type'] : 'text';
        $required = ! empty($field['required']) ? 'required' : '';
        $name     = sanitize_key($field['label']) ?: 'field_' . wp_generate_uuid4();

        switch ($type) {
            case 'textarea':
                return '<textarea id="' . esc_attr($fieldId) . '" name="fields[' . esc_attr($name) . ']" ' . $required . '></textarea>';
            case 'email':
                return '<input type="email" id="' . esc_attr($fieldId) . '" name="fields[' . esc_attr($name) . ']" ' . $required . '>';
            case 'select':
                return '<select id="' . esc_attr($fieldId) . '" name="fields[' . esc_attr($name) . ']" ' . $required . '></select>';
            default:
                return '<input type="text" id="' . esc_attr($fieldId) . '" name="fields[' . esc_attr($name) . ']" ' . $required . '>';
        }
    }
}
