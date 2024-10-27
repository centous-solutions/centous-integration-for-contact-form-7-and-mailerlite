<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @class    CCF7M_MailerLite_Integration_Class
 * @category Class
 * @author   Nikhil Tiwari
 */
class CCF7M_MailerLite_Integration_Class
{
    protected static $_instance = null;
    public $glSettings = array();

    // Get singleton instance
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Constructor
    public function __construct()
    {
        $this->hooks();
    }

    // Register hooks
    public function hooks()
    {
        // Enqueue necessary styles and scripts for admin pages
        add_action('admin_enqueue_scripts', array($this, 'ccf7m_enqueue_styles_scripts'));

        // Add a custom MailerLite tab to the CF7 form editor
        add_filter('wpcf7_editor_panels', array($this, 'ccf7m_add_mailerlite_tab'));

        // Save MailerLite settings when a CF7 form is saved
        add_action('wpcf7_save_contact_form', array($this, 'ccf7m_save_mailerlite_settings'));

        // Handle the submission of CF7 forms to MailerLite
        add_action('wpcf7_mail_sent', array($this, 'ccf7m_cf7_to_mailerlite'));

        // Register AJAX action hooks for logged-in and non-logged-in users to verify the MailerLite API key
        add_action('wp_ajax_verify_mailerlite_api_key', array($this, 'ccf7m_verify_mailerlite_api_key_callback'));
        add_action('wp_ajax_nopriv_verify_mailerlite_api_key', array($this, 'ccf7m_verify_mailerlite_api_key_callback'));
    }

    /**
     * Callback function to verify the MailerLite API key.
     * This function will be called via AJAX when a request is made.
     */
    public function ccf7m_verify_mailerlite_api_key_callback()
    {
        // Verify the AJAX nonce for security
        check_ajax_referer('save_mailerlite_settings', 'nonce');

        // Get the API key and form id from the AJAX request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        // Verify the API key using the previously defined method
        $is_verified = $this->ccf7m_verify_mailerlite_api_key($api_key);

        // Check if the API key verification was successful
        if ($is_verified) {
            // Update the post meta with the valid API key
            update_post_meta($post_id, '_mailerlite_api_key', $api_key);
            // Send a successful JSON response back to the AJAX request
            wp_send_json_success('API key verified successfully.');
        } else {
            // The API key verification failed
            wp_send_json_error('Invalid API key. Please try again.');
        }
    }

    /**
     * Send CF7 form data to MailerLite
     *
     * @param object $contact_form CF7 contact form object
     */
    public function ccf7m_cf7_to_mailerlite($contact_form)
    {
        // Get the ID of the submitted form
        $form_id = $contact_form->id();
        // Get the current submission instance
        $submission = WPCF7_Submission::get_instance();

        // Check if a submission exists
        if ($submission) {
            // Get posted form data
            $form_data  = $submission->get_posted_data();
            // Retrieve MailerLite API key and selected group ID from post meta
            $api_key = get_post_meta($form_id, '_mailerlite_api_key', true);

            // Prepare data for MailerLite based on CF7 fields and MailerLite fields
            $fields_to_send = $this->ccf7m_prepare_mailerlite_fields($form_id, $form_data, $api_key);
            // Send the prepared data to MailerLite
            $this->ccf7m_send_to_mailerlite($api_key, $fields_to_send);
        }
    }

    /**
     * Prepare fields to send to MailerLite
     *
     * @param int $post_id CF7 form ID
     * @param array $form_data Submitted form data
     * @param string $api_key MailerLite API key
     * @return array Prepared fields for MailerLite
     */
    public function ccf7m_prepare_mailerlite_fields($post_id, $form_data, $api_key)
    {
        // Retrieve field mappings from post meta
        $mailerlite_field_mappings = get_post_meta($post_id, '_mailerlite_field_mappings', true) ?: [];

        // Fetch MailerLite fields
        $mailerlite_fields = $this->ccf7m_mailerlite_api_response('fields', $api_key);

        $fields = [];

        // Loop through MailerLite fields to map CF7 fields
        foreach ($mailerlite_fields as $mailerlite_field) {
            // Get the mapped CF7 field based on MailerLite key
            $cf7_field_key = $mailerlite_field_mappings[$mailerlite_field['key']] ?? null;

            if ($cf7_field_key) {
                // Clean the CF7 field key to avoid unwanted characters
                $cf7_field_clean_key = preg_replace('/\[.*?\s|\]/', '', $cf7_field_key);
                // Check if the form data has the cleaned field key
                if (isset($form_data[$cf7_field_clean_key])) {
                    $field_value = $form_data[$cf7_field_clean_key];

                    // Handle array values (e.g., checkboxes or multiple selections)
                    if (is_array($field_value)) {
                        // Convert array to comma-separated string
                        $field_value = implode(', ', $field_value);
                    }
                }

                // Assign values to appropriate fields for MailerLite
                if ($mailerlite_field['key'] === 'email') {
                    $fields['email'] = $field_value; // Directly assign email
                } elseif ($mailerlite_field['key'] === 'name') {
                    $fields['name'] = $field_value; // Directly assign name
                } else {
                    // Other fields go into the nested 'fields' array
                    $fields['fields'][$mailerlite_field['key']] = $field_value;
                }
            }
        }
        // Return the prepared fields
        return $fields;
    }

    /**
     * Send data to MailerLite API
     *
     * @param string $api_key MailerLite API key
     * @param string $group_id MailerLite group ID
     * @param array $fields Data to send to MailerLite
     */
    public function ccf7m_send_to_mailerlite($api_key, $fields)
    {
        // Construct the API endpoint URL for adding subscribers
        $url = 'https://api.mailerlite.com/api/v2//subscribers';
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-MailerLite-ApiKey' => $api_key,
            ],
            'body' => wp_json_encode($fields),
            'method' => 'POST',
            'data_format' => 'body',
        ];

        // Make the request to MailerLite
        $response = wp_remote_post($url, $args);

        // Check for errors in the response
        if (is_wp_error($response)) {
            // Handle error and log the message
            $error_message = $response->get_error_message();
            error_log('MailerLite API error: ' . $error_message);
        } else {
            // Success logic if needed
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            // Check if the subscriber was successfully added
            if (isset($response_data['id'])) {
                // Successfully added to MailerLite
            } else {
                // Handle error response
                error_log('MailerLite API response error: ' . print_r($response_data, true));
            }
        }
    }


    /**
     * Save and verify MailerLite API key and settings
     *
     * @param object $contact_form CF7 contact form object
     */
    public function ccf7m_save_mailerlite_settings($contact_form)
    {
        // Verify nonce for security
        if (!isset($_POST['mailerlite_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mailerlite_settings_nonce'])), 'save_mailerlite_settings')) {
            return; // Invalid nonce
        }
        $post_id = $contact_form->id();
        // Get the ID of the current form
        $api_key = get_post_meta($post_id, '_mailerlite_api_key', true);

        // Verify the MailerLite API key before saving
        if ($this->ccf7m_verify_mailerlite_api_key($api_key)) {

            // Check if field mappings are set
            if (isset($_POST['mailerlite_settings'])) {
                // Sanitize and save field mappings
                $mappings = array_map('sanitize_text_field', wp_unslash($_POST['mailerlite_settings']));


                // Update the post meta with the mappings
                $updated_fields = update_post_meta($post_id, '_mailerlite_field_mappings', $mappings);

                //add params on url 
                if ($updated_fields) {
                    $redirect_url = admin_url('admin.php?page=wpcf7&post=' . $post_id . '&action=edit&active-tab=4&section=field-mapping');
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
    }


    /**
     * Add the MailerLite settings tab to the CF7 editor.
     *
     * @param array $panels Existing editor panels.
     * @return array Modified panels with MailerLite tab.
     */
    public function ccf7m_add_mailerlite_tab($panels)
    {
        // Define a new panel for MailerLite settings
        $panels['mailerlite-integration-panel'] = [
            'title'    => __('MailerLite Integration', 'centous-integration-for-contact-form-7-and-mailerlite'),
            'callback' => [$this, 'ccf7m_render_mailerlite_panel_content'],
        ];

        // Create a new array to maintain the desired order
        $new_order = [];

        $current_index = 0;

        foreach ($panels as $key => $panel) {
            if ($current_index === 4) {
                // Add MailerLite tab before the current tab if we reached the fourth position
                $new_order['mailerlite-integration-panel'] = $panels['mailerlite-integration-panel'];
            }
            $new_order[$key] = $panel;
            $current_index++;
        }

        // If we have fewer than 4 tabs, just append it at the end
        if ($current_index < 4) {
            $new_order['mailerlite-integration-panel'] = $panels['mailerlite-integration-panel'];
        }

        return $new_order;
    }

    /**
     * Render the content for the MailerLite Integration tab
     *
     * @param object $post The CF7 form post object.
     */
    public function ccf7m_render_mailerlite_panel_content($post)
    {
        $data = array(
            'post' => $post
        );
        helper_ccf7m_object()->ccf7m_get_template('ccf7m-api-key.php', 'cf7-mailerLite-integration-module', $data, false);
    }

    /**
     * Renders the MailerLite settings section in the form editor.
     *
     * @param WP_Post $post The current post object.
     */
    public function ccf7m_setting_section_mailerLite($post)
    {
        // Prepare data to be passed to the template
        $data = array(
            'post' => $post
        );
        // Load the MailerLite settings template
        helper_ccf7m_object()->ccf7m_get_template('ccf7m-api-setting-tabs.php', 'cf7-mailerLite-integration-module', $data, false);
    }

    /**
     * Verifies the MailerLite API key by making a request to the MailerLite API.
     *
     * @param string $api_key The API key to be verified.
     * @return bool Returns true if the API key is valid, false otherwise.
     */
    public function ccf7m_verify_mailerlite_api_key($api_key)
    {
        // Make the request to verify the API key
        $response = wp_remote_get('https://api.mailerlite.com/api/v2/subscribers', [
            'headers' => [
                'X-MailerLite-ApiKey' => $api_key,
            ],
        ]);

        // Check if the response is valid and the API key is working
        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        // Assuming 200 is the success code for MailerLite API
        return $status_code === 200;
    }

    /**
     * Retrieves the response from a specific MailerLite API endpoint using the provided API key.
     *
     * @param string $endpoint The API endpoint to call (e.g., 'subscribers').
     * @param string $api_key The MailerLite API key to use for authentication.
     * @return array Returns the decoded JSON response as an associative array, or an empty array on error.
     */
    public function ccf7m_mailerlite_api_response($endpoint, $api_key)
    {
        // Make the API request to the specified endpoint
        $response = wp_remote_get('https://api.mailerlite.com/api/v2/' . $endpoint . '', [
            'headers' => [
                'X-MailerLite-ApiKey' => $api_key,
            ],
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $sections = json_decode($body, true);

        return !empty($sections) ? $sections : [];
    }

    /**
     * Retrieves all shortcodes (fields) used in a Contact Form 7 form by its ID.
     *
     * @param int $post_id The ID of the Contact Form 7 post.
     * @return array An array of shortcodes representing the form fields.
     */
    public function ccf7m_get_contact_from7_fields($post_id)
    {
        // Get the current form instance
        $form = WPCF7_ContactForm::get_instance($post_id);
        // Get the form content (HTML with shortcodes)
        $form_content = $form->prop('form');

        $cf7_shortcodes = [];


        // Regular expression to match all shortcodes (e.g., [text* your-name])
        if (preg_match_all('/\[(\w+)(\*?)\s+([^\]]+)\]/', $form_content, $matches, PREG_SET_ORDER)) {

            foreach ($matches as $shortcode) {
                $field_type = $shortcode[1]; // e.g., email, text, select, checkbox
                $is_required = $shortcode[2]; // If the field is required (denoted by '*')
                $field_details = $shortcode[3]; // Get the rest of the shortcode content

                // Split field details by space and take the first element as the field name
                $field_name = explode(' ', $field_details)[0]; // e.g., your-email, your-subject

                // Store the shortcode in an array
                $cf7_shortcodes[] = '[' . esc_attr($field_type . $is_required . ' ' . $field_name) . ']'; // e.g., [email* your-email]
            }
        }
        return $cf7_shortcodes;
    }

    /**
     * Renders the field mapping section in the form editor.
     *
     * @param WP_Post $post The current post object.
     */
    public function ccf7m_field_mapping_section($post)
    {
        // Prepare data to be passed to the template
        $data = array(
            'post' => $post
        );
        // Load the field mapping settings template
        helper_ccf7m_object()->ccf7m_get_template('ccf7m-field-mapping-page.php', 'cf7-mailerLite-integration-module', $data, false);
    }

    /**
     * Renders the documentation section for the MailerLite integration.
     * This function is responsible for preparing data and loading the corresponding template.
     */
    public function ccf7m_documentation_section()
    {
        // Prepare data to be passed to the template
        $data = array();

        // Load the field mapping settings template
        helper_ccf7m_object()->ccf7m_get_template('ccf7m-documentation-page.php', 'cf7-mailerLite-integration-module', $data, false);
    }

    /**
     * Enqueue stylesheets and JavaScript files
     */
    public function ccf7m_enqueue_styles_scripts()
    {
        // Check user capabilities to ensure they have access
        if (!current_user_can('manage_options')) {
            return;
        }
        // Register the style
        wp_register_style('cf7_mailerLite_style', CCF7M_URL . 'modules/cf7-mailerLite-integration-module/css/style.css', array(), CCF7M_VERSION, 'all');
        wp_enqueue_style('cf7_mailerLite_style');

        // Register the script
        wp_register_script('cf7_mailerLite-script', CCF7M_URL . 'modules/cf7-mailerLite-integration-module/js/script.js', array('jquery'), CCF7M_VERSION, true);
        wp_enqueue_script('cf7_mailerLite-script');

        // Pass Ajax URL to the JavaScript file
        wp_localize_script('cf7_mailerLite-script', 'wep_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('save_mailerlite_settings'),
        ));
    }
}

// Function to get the singleton instance
function class_ccf7m_mailerLite_Integration_object()
{
    return CCF7M_MailerLite_Integration_Class::get_instance();
}
// Initialize the class
class_ccf7m_mailerLite_Integration_object();
