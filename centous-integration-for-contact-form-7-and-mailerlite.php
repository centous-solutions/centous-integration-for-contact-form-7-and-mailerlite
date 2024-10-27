<?php

/**
 * Plugin Name: Centous Integration For Contact Form 7 And MailerLite
 * Plugin URI: https://www.centous.com/centous-integration-cf7-mailerlite
 * Description: Seamlessly integrate MailerLite with Contact Form 7 to add subscribers directly from WordPress.
 * Version: 1.0.0
 * Author: centous
 * Author URI: https://www.centous.com/
 * Text Domain: centous-integration-for-contact-form-7-and-mailerlite
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define required version
define('CCF7M_REQUIRED_WP_VERSION', '6.1.1');

// Check WordPress version
if (version_compare($GLOBALS['wp_version'], CCF7M_REQUIRED_WP_VERSION, '<')) {
    wp_die(esc_html__('This plugin requires WordPress version 6.1.1 or higher.', 'centous-integration-for-contact-form-7-and-mailerlite'));
}

// Define constants
define('CCF7M_VERSION', '1.0.0');
define('CCF7M_PATH', plugin_dir_path(__FILE__));
define('CCF7M_URL', plugin_dir_url(__FILE__));


// create a class where we call all the function, hook, and classes----------
if (!class_exists('CCF7M_Integration_Core')) {
    class   CCF7M_Integration_Core
    {
        // first call create a cunstruct function 
        public function __construct()
        {
            /**-----------------------
             * include files
                 ----------------------------------*/
            require(CCF7M_PATH . 'includes/activation.php');
        }
    }
    $ccf7m_integration_core  = new CCF7M_Integration_Core();
}
