<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @class    CCF7M_Included_Files
 * @category Class
 * @author   Nikhil Tiwari
 */

class CCF7M_Included_Files
{
    public function __construct()
    {

        // Include helper file
        $this->CCF7M_integration_require_file('helper.php');

        // Include entries form module class
        $this->CCF7M_integration_require_file('cf7-mailerLite-integration-module/cf7-mailerLite-integration-class.php');
    }

    /**
     * Safely require a file
     *
     * @param string $file_name The file name to include
     */
    private function CCF7M_integration_require_file($file_name)
    {
        $file_path = plugin_dir_path(__FILE__) . $file_name;

        // Check if the file exists before including it
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Log error or handle it appropriately
            error_log(sprintf('File %s not found in %s', $file_name, __METHOD__));
        }
    }
}
new CCF7M_Included_Files();
