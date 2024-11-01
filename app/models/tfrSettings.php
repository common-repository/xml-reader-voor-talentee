<?php 

if (!defined('ABSPATH')) exit;

class tfrSettings
{
    /**
     * On plugin initialisation, we need to check if the settings are set.
     * @return              Boolean         Returns true on success, false on failure.
     */
    public static function init()
    {
        if (get_option(TFR_OPTION_KEY) == false) {
            $defaultSettings = array(
                'settingsInit' => 0,
                'cptKeyword' => 'vacatures',
                'feedUrl' => '',
                'officeID' => '',
                'deleteOnExpiry' => true,
                'useWPSchedule' => true,
                'updateFrequency' => 'daily',
                'updateTime' => '03:00',
                'updateFrequencyCron' => '00 03 * * *',
                'flushRewriteRules' => true,
            );

            if (add_option(TFR_OPTION_KEY, $defaultSettings)) {
                tfrLogger::add('Notice: Settings table created.');
                return true;
            }

            tfrLogger::add('Warning: Something went wrong when creating the settings table.');
            return false;
        }
    }

    /**
     * Get a specific settings by $field name, or return all settings if $field is not set.
     * @param  String       $field          The settings name to lookup. Optional, default value null
     * @return Mixed                        Returns the value the setting, an array when $field is not set or false on failure.
     */
    public static function get($field = null)
    {
        $options = get_option(TFR_OPTION_KEY);
        if ($field !== null && isset($options[$field])) {
            return $options[$field];
        }

        return $options;
    }

    /**
     * Save the settings to the database
     * @param  Array        $settings       An array containing the setting name as key and it's value as value.
     * @return Boolean                      True on success, false on failure.
     */
    public static function save($settings)
    {
        $currentSettings = self::get();

        if (isset($settings['checkboxesAreEvil'])) {
            if (!isset($settings['deleteOnExpiry'])) {
                $settings['deleteOnExpiry'] = false;
            }
            if (!isset($settings['useWPSchedule'])) {
                $settings['useWPSchedule'] = false;
            }
        }

        if (isset($settings['cptKeyword']) && $currentSettings['cptKeyword'] != $settings['cptKeyword']) {
            $currentSettings['flushRewriteRules'] = true;
        }

        foreach ($currentSettings as $optionName => $value) {
            if (isset($settings[$optionName])) {
                $currentSettings[$optionName] = $settings[$optionName];
            }
        }

        if ($currentSettings['cptKeyword'] === '') {
            $currentSettings['cptKeyword'] = 'vacatures';
        }

        update_option(TFR_OPTION_KEY, $currentSettings);
    }

    /**
     * Checks for the minimum required PHP Version.
     * @return              Boolean
     */
    public static function checkPHPVersion()
    {
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
        }

        if (PHP_VERSION_ID < 50100) {
            wefactLogger::add('Warning: PHP version too low for plugin! PHP '.PHP_VERSION_ID.' found, PHP >= 5.1.0 required!');
            return false;
        }

        return true;
    }

    /**
     * Checks for the existence of cURL
     * @return              Boolean 
     */
    public static function checkCURL()
    {
        if (function_exists('curl_version')) {
            return true;
        }

        wefactLogger::add('Warning: cURL extension not found!');
        return false;
    }
    
    /**
     * Delete all settings from the database
     * @return              Boolean
     */
    public static function delete()
    {
        return delete_option(TFR_OPTION_KEY);
    }
}
