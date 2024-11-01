<?php

if (!defined('ABSPATH')) exit;

class tfrValidator {

	public function __construct()
	{
		require_once(TFR_PLUGIN_DIR.'/app/models/tfrRules.php');
	}

	/**
	 * Validate the input names
	 * @param  String       $inputName      The input name to validate
	 * @return Boolean
	 */
	public function validateName($inputName)
	{
	    $knownInputNames = array('feedUrl', 'cptKeyword', 'deleteOnExpiry', 'useWPSchedule', 'checkboxesAreEvil', 'officeID');

	    foreach ($knownInputNames as $knownInputName) {
	        if ($knownInputName == $inputName) {
	            return true;
	        }
	    }

	    return false;
	}

	/**
	 * Validate a $inputValue by a set of $rules. Uses the TFRValidator class.
	 * @param  String       $inputValue     The input value to validate
	 * @param  Array        $rules          An array of rules to check
	 * @return Boolean
	 */
	public function validateValue($inputValue, $rules)
	{
	    if (is_null($rules)) {
	        return true;
	    }

	    $rules = explode('|', $rules);

	    if (array_search('required', $rules) === false && empty($inputValue)) {
	        return true;
	    }

	    foreach ($rules as $rule) {
	        if (!call_user_func('tfrRules::'.$rule, $inputValue)) {
	            return false;
	        }
	    }

	    return true;
	}

}
