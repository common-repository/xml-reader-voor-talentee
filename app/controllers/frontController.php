<?php
if (!defined('ABSPATH')) exit;

function initialiseTFR() {

	require_once(TFR_PLUGIN_DIR.'/app/controllers/customPostController.php');
	$vacancyPostType = new tfrCustomPostController();
	$vacancyPostType->register();

	if (tfrSettings::get('flushRewriteRules')) {
		flush_rewrite_rules();
		tfrSettings::save(array('flushRewriteRules' => false));
		tfrLogger::add('Notice: Flushed the rewrite rules. This usually happens when the keyword changes for the Custom Post Type.');
	}
	
	require_once(TFR_PLUGIN_DIR.'/app/controllers/adminController.php');
	$admin = new tfrAdminController();
	$admin->initialiseActions();

    add_action('wp_ajax_tfrAjaxUpdate', 'tfrAjaxUpdateCallback');
    add_action('tfrUpdateHook', 'tfrAjaxUpdateCallback', 10, 1);
    add_action('widgets_init', 'registerTfrWidget');

    if (strpos($_SERVER['REQUEST_URI'],tfrSettings::get('cptKeyword')) !== false && isset($_GET['updateSecret']) && $_GET['updateSecret'] == 'R97ArcTgmFnBhD') {
        tfrAjaxUpdateCallback('cron');
    }

    tfrSettingsChange();
}


function registerTfrWidget() {
    require_once(TFR_PLUGIN_DIR.'/app/controllers/widgetController.php');
    register_widget('tfrWidget');
}


function tfrAjaxUpdateCallback($type = null) {
    require_once(TFR_PLUGIN_DIR.'/app/helpers/tfrUpdate.php');
    $update = new tfrUpdate();
    if ($type == 'wpSchedule') {
        if (tfrSettings::get('useWPSchedule')) {
            $response = $update->update('scheduled');
        }
    } elseif ($type == 'cron') {
        $response = $update->update('scheduled');
    } elseif ($type == 'manual') {
        $response = $update->update('manual');
    } else {
        tfrLogger::add('Notice: unable to determine update method, assuming manual. This has no negative effect on the import process.');
        $response = $update->update('manual');
    }
    
    echo $response;
    exit();
}

function tfrSettingsChange() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['page']) && $_GET['page'] == 'tfr_settings') {
        include(TFR_PLUGIN_DIR.'/app/models/tfrValidator.php');
        $validation = new tfrValidator();
        $clean = array();
        $rules = array(
            'checkboxesAreEvil' => 'boolean',
            'feedUrl' => 'url',
            'cptKeyword' => 'alphanumeric',
            'deleteOnExpiry' => 'boolean',
            'useWPSchedule' => 'boolean',
            'officeID' => 'alphanumeric',
        );

        foreach ($_POST as $inputName => $inputValue) {
            if (!$validation->validateName($inputName)) {
                continue;
            }
            if (!$validation->validateValue($inputValue, $rules[$inputName])) {
                $admin->displayErrorMessage("The field $inputName has not a valid value! Therefore it was not saved.", 'error');
                continue;
            }
            $clean[$inputName] = $inputValue;
        }

        tfrSettings::save($clean);
        define('TFR_UPDATE', true);
    }
}

function tfrLoadTranslations() {
    load_plugin_textdomain('talentee-feedreader', false, 'talentee-feedreader/lang/');
}

function tfrdisplayErrorMessage($message, $type = 'updated') {
    $html = '<div id="message" class="message ' . $type . '"><p>' . $message . '</p></div>';
    add_action('admin_notices', function() use ($html) {
        echo $html;
    });
}
