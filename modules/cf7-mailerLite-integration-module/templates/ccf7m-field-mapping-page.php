<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$api_key = get_post_meta($post->id(), '_mailerlite_api_key', true);

// Fetch all MailerLite fields
$mailerlite_fields = class_ccf7m_mailerLite_Integration_object()->ccf7m_mailerlite_api_response('fields', $api_key);

// Get the Contact Form 7 form content and extract the shortcodes
$cf7_shortcodes = class_ccf7m_mailerLite_Integration_object()->ccf7m_get_contact_from7_fields($post->id());

// Retrieve saved MailerLite settings
$saved_mailerlite_settings = get_post_meta($post->ID(), '_mailerlite_field_mappings', true) ?: []; // Default to empty array if not set

// Create a nonce field for security
$nonce = wp_create_nonce('save_mailerlite_settings');

if (class_ccf7m_mailerLite_Integration_object()->ccf7m_verify_mailerlite_api_key($api_key)) {
?>

    <!-- WordPress form-table class for default styling -->
    <table class="form-table params-mailerlite">
        <thead>
            <tr>
                <th><?php esc_html_e('MailerLite Field', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></th>
                <th><?php esc_html_e('Contact Form 7 Field', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Nonce field -->
            <input type="hidden" name="mailerlite_settings_nonce" value="<?php echo esc_attr($nonce); ?>">

            <?php foreach ($mailerlite_fields as $mailerlite_field) :
                $saved_value = isset($saved_mailerlite_settings[$mailerlite_field['key']]) ? $saved_mailerlite_settings[$mailerlite_field['key']] : '';
            ?>
                <tr>
                    <td><?php echo esc_html($mailerlite_field['title']); ?></td>
                    <td>
                        <!-- WordPress default styling for selects -->
                        <select id="mailerlite_settings_<?php echo esc_attr($mailerlite_field['key']); ?>" name="mailerlite_settings[<?php echo esc_attr($mailerlite_field['key']); ?>]" class="regular-text">
                            <option value=""><?php esc_html_e('Select a form field', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></option>
                            <?php foreach ($cf7_shortcodes as $shortcode) : ?>
                                <option value="<?php echo esc_attr($shortcode); ?>" <?php selected($saved_value, $shortcode); ?>>
                                    <?php echo esc_html($shortcode); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
}
?>