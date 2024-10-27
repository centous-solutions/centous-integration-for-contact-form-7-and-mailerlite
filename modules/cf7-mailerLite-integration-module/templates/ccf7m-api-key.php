<?php
echo "<pre>";
print_r('asdfasd');
echo "</pre>";
die();
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$api_key = get_post_meta($post->id(), '_mailerlite_api_key', true);
$is_verified = false;


// Get the saved data
$field_mapping_data = get_post_meta($post->id(), '_mailerlite_field_mapping', true);

// If there's no saved data, set empty defaults
$name_value = isset($field_mapping_data['name']) ? esc_attr($field_mapping_data['name']) : '';
$email_value = isset($field_mapping_data['email']) ? esc_attr($field_mapping_data['email']) : '';



// Check if an API key exists and is valid
if (!empty($api_key) && class_ccf7m_mailerLite_Integration_object()->ccf7m_verify_mailerlite_api_key($api_key)) {
    $is_verified = true;
}
if ($is_verified) {
    class_ccf7m_mailerLite_Integration_object()->ccf7m_setting_section_mailerLite($post);
} else { ?>
    <!-- Message when API key is not verified -->
    <p><b><?php esc_html_e('Please enter and verify a valid MailerLite API key to enable additional settings.', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></b></p>
<?php } ?>
<br>
<div id="content-general" class="tab-content">
    <!-- General Settings Content -->
    <div class="inside">

        <form action=""></form>
        <form id="mailerlite-api-key-form" method="post" action="">
            <?php wp_nonce_field('save_mailerlite_settings', 'mailerlite_settings_nonce'); ?>

            <input type="hidden" name="post_id" id="mailerlite-post-id" value="<?php echo esc_attr($post->id()); ?>">
            <label for="mailerlite-api-key"><?php esc_html_e('API Key', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></label>
            <input type="text" name="mailerlite-api-key" id="mailerlite-api-key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">

            <input type="submit" class="button button-primary verify-button" value="<?php esc_attr_e('Verify', 'centous-integration-for-contact-form-7-and-mailerlite'); ?>">
        </form>
        <div id="mailerlite-message" style="margin-top: 10px;"></div>
    </div>
</div>

<div id="content-field-mapping" class="tab-content" style="display: none;">
    <?php
    class_ccf7m_mailerLite_Integration_object()->ccf7m_field_mapping_section($post);
    ?>
</div>

<div id="content-documentation-support" class="tab-content" style="display: none;">
    <?php
    class_ccf7m_mailerLite_Integration_object()->ccf7m_documentation_section();
    ?>
</div>