<?php

/**
 * @package Centous Integration For Contact Form 7 And MailerLite
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ccf7m_integration_activation')) {
    function ccf7m_integration_activation()
    {

        // Check if the required file exists before including it
        $included_file = CCF7M_PATH . 'modules/included_files.php';

        if (file_exists($included_file)) {
            include($included_file);
        } else {
            // Optional: Add error handling or logging for missing files
            error_log('Required included_files.php not found in path: ' . $included_file);
        }
    }
}
ccf7m_integration_activation();
