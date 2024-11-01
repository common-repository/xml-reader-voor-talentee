<?php

if (!defined('ABSPATH')) exit;

class tfrAdminController 
{
    
    public function initialiseActions()
    {
        add_action('admin_menu', array($this, 'tfrSettingsPage'));
        add_action('load-post.php', array($this, 'tfrMetaBoxSetup'));
        add_action('load-post-new.php', array($this, 'tfrMetaBoxSetup'));
        add_action('admin_enqueue_scripts', array($this, 'tfrLoadAdminScripts'));
    }

    /**
     * Basic function to display error messages to the wp-admin user.
     * @param  String   $message    The message to be displayed
     * @param  String   $type       (Optional) The type of message
     * @return Response
     */
    public function displayErrorMessage($message, $type = 'updated') {
        $html = '<div id="message" class="message ' . $type . '"><p>' . $message . '</p></div>';
        add_action('admin_notices', function() use ($html) {
            echo $html;
        });
    }

    /**
     * The setup to add the MetaBox and add the required CSS and validator
     */
    public function tfrMetaBoxSetup()
    {
        add_action('add_meta_boxes', array($this, 'tfrAddMetaBox'));
        add_action('save_post', array($this, 'tfrProcessPostRequest'), 10, 2);
    }

    /**
     * The callback function that adds the MetaBox to the admin screen
     */
    public function tfrAddMetaBox()
    {
        add_meta_box(
            'tfrPostClass',
            'Talentee Feedreader',
            array($this, 'tfrLoadForm'),
            'tfr_vacancies',
            'normal',
            'high'
        );
    }

    public function tfrLoadAdminScripts()
    {
        wp_register_style('tfrAdminStyle', TFR_PLUGIN_URL . 'assets/css/admin.index.css', false, TFR_VERSION);
        wp_enqueue_style('tfrAdminStyle');

        wp_register_script('tfrAdminScripts', TFR_PLUGIN_URL . 'assets/js/admin.tabs.js', array('jquery'), TFR_VERSION);
        wp_enqueue_script('tfrAdminScripts');
    }

    /**
     * The callback function that loads the form within the MetaBox
     * @param  WP_Post      $object         The Wordpress post object
     * @param  Array        $box        
     */
    public function tfrLoadForm($object, $box)
    { 
        require_once(TFR_PLUGIN_DIR.'/app/views/admin.create.php');
    }

    /**
     * The callback function that loads the settings page
     */
    public function tfrLoadSettingsPage()
    {

        require_once(TFR_PLUGIN_DIR.'/app/views/admin.settings.php');
        
    }

    /**
     * Setup the settings page for this plugin
     */
    public function tfrSettingsPage()
    {
        add_submenu_page(
            'edit.php?post_type=tfr_vacancies', 
            'Talentee Feedreader Instellingen', 
            'Talentee Feedreader Instelling', 
            'edit_posts', 
            'tfr_settings', 
            array($this, 'tfrLoadSettingsPage')
        );
    }

    /**
     * Processes the post request and validates it.
     * @param  Int          $post_id        The post' ID 
     * @param  WP_Post      $post           The Wordpress post object
     * @return Boolean
     */
    public function tfrProcessPostRequest($post_id, $post)
    {
        if (isset($_POST['tfrForm']) && is_array($_POST['tfrForm'])) {
            $formData = array();

            foreach ($_POST['tfrForm'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $formData[$key][$subKey] = sanitize_text_field($subValue);
                    }
                } else {
                    $formData[$key] = sanitize_text_field($value);
                }
            }

            update_post_meta($post->ID, TFR_META_KEY, $formData);
        }
    }

}
