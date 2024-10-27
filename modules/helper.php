<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @class    CF7MI_Helper
 * @category Class
 * @author   Nikhil
 **/
class CCF7M_Helper
{
    public static $_instance = null;
    public static function get_instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {}

    /**
     * Load any template from a specific folder
     *
     * @param string $template_name Name of the template file
     * @param string $template_path Path to the template folder
     * @param array $args Arguments to be extracted into the template
     * @param bool $return Whether to return the output instead of echoing it
     * @return mixed|null Rendered template or null
     */
    public function ccf7m_get_template($template_name, $template_path = '', $args = array(), $return = false)
    {
        $located = $this->ccf7m_locate_template($template_name, $template_path);
        if ($args && is_array($args)) {
            extract($args);
        }
        if ($return) {
            ob_start();
        }
        // include file located
        if (file_exists($located)) {
            include $located;
        }
        if ($return) {
            return ob_get_clean();
        }
    }

    /**
     * Find the location for a template
     *
     * @param string $template_name Name of the template file
     * @param string $template_path Path to the template folder
     * @return string Template file path
     */
    public function ccf7m_locate_template($template_name, $template_path)
    {
        $template = __DIR__ . '/' . $template_path . '/templates/' . $template_name;
        return $template;
    }
}
function helper_ccf7m_object()
{
    return CCF7M_Helper::get_instance();
}
helper_ccf7m_object();
