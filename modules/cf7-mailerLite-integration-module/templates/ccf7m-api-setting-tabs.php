<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<ul class="subsubsub api-key-sub-settings mb-30" data-post-id="<?php echo esc_attr($post->id()); ?>">
    <li><a href="#" class="tab-link" data-section="general"><?php esc_html_e('General', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></a> | </li>
    <li><a href="#" class="tab-link" data-section="field-mapping"><?php esc_html_e('Field Mapping', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></a> | </li>
    <li><a href="#" class="tab-link" data-section="documentation-support"><?php esc_html_e('Documentation/Support', 'centous-integration-for-contact-form-7-and-mailerlite'); ?></a></li>
</ul>