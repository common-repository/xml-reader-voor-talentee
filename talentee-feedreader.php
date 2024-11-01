<?php
/**
 * Plugin Name: XML Reader voor Talantee vacatures
 * Text Domain: talentee-feedreader
 * Plugin URI: https://tussendoor.nl/wordpress-plugins/
 * Description: Deze plugin leest de vacatures van Talentee uit en maakt hier nieuwe berichten van.
 * Version: 1.0.3
 * Author: Tussendoor internet & marketing
 * Author URI: https://tussendoor.nl/
 * Requires at least: 3.0
 * Tested up to: 4.8
 * Requires at least PHP 5.1
 */

if (!defined('ABSPATH')) exit;

define('TFR_PLUGIN_DIR', dirname(__FILE__));
define('TFR_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('TFR_VERSION', '1.0.3');
define('TFR_CPT', 'tfr_vacancies');
define('TFR_META_KEY', 'tfr_data');
define('TFR_OPTION_KEY', 'tfr_settings');

/**
 * Load the logging library
 */
require_once(TFR_PLUGIN_DIR.'/app/models/tfrLogger.php');

/**
 * Load the settings model
 */
require_once(TFR_PLUGIN_DIR.'/app/models/tfrSettings.php');

/**
 * Load the front controller which initialises the plugin
 */
require_once(TFR_PLUGIN_DIR.'/app/controllers/frontController.php');

function tfrPluginActivation() {
    tfrLogger::init();
    tfrSettings::init();

    if (tfrSettings::get('deleteOnExpiry')) {
        if (!wp_next_scheduled ('deleteOnExpiryEvent')) {
            wp_schedule_event(time()+10, 'hourly', 'deleteOnExpiryEvent');
        }
    }
    
    if (tfrSettings::get('useWPSchedule')) {
        if (!wp_next_scheduled('tfrUpdateHook')) {
            wp_schedule_event(time()+10, 'daily', 'tfrUpdateHook');
        }
    }
}

function tfrPluginDeactivation() {
    wp_clear_scheduled_hook('tfrUpdateHook');
    wp_clear_scheduled_hook('deleteOnExpiryEvent');
}

register_activation_hook(__FILE__, 'tfrPluginActivation');
register_deactivation_hook(__FILE__, 'tfrPluginDeactivation');

add_action('init', 'initialiseTFR', 0);

add_action('plugins_loaded', 'tfrLoadTranslations');
