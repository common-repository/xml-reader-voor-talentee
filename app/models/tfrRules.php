<?php

if (!defined('ABSPATH')) exit;

class tfrRules {

	/**
	 * Checks if the $input is set
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function required($input)
	{
		if (!empty($input)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the $input is a number (or is numeric)
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function number($input)
	{
		if (is_numeric($input)) {
			return true;
		}
		
		return false;
	}

	/**
	 * Performs a regex match on $input
	 * @param  String 		$pattern 		The regex pattern to perform
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function regex($pattern, $input)
	{
		return preg_match($pattern, $input);
	}

	/**
	 * Checks if the $input is within the $maxLength
	 * @param  Integer 		$maxLength 		The maximum length of the string
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function length($maxLength, $input)
	{
		if (strlen($input) < $maxLength) {
			return true;
		}

		return false;
	}

	/**
	 * Trims the $input to the $maxLength
	 * @param  Integer 		$maxLength 		The maximum length of the string
	 * @param  String 		$input
	 * @return String
	 */
	public static function trim($maxLength, $input)
	{
		return substr($input, 0, $maxLength);
	}

	/**
	 * Performs a regex to check if $input is a dutch zipcode.
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function zipcode($input)
	{
		return preg_match('~\A[1-9]\d{3} ?[a-zA-Z]{2}\z~', $input);
	}

	/**
	 * Checks if the $input can be exploded into an array. Then checks if the values are numeric.
	 * @param  String 		$input 
	 * @return Boolean
	 */
	public static function year($input)
	{
		$array = explode('|', $input);
		if (!count($array)) {
			return false;
		}

		foreach ($array as $year) {
			if (preg_match('/[^0-9\s-]+/', $year)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if $input is a valid url. 
	 * @param  String 		$input
	 * @return Boolean
	 */
	public static function url($input)
	{
		if (filter_var($input, FILTER_VALIDATE_URL) !== false) {
			return true;
		}

		return false;
	}


	public static function boolean($input)
	{
		return (bool)$input;
	}


	public static function alphanumeric($input)
	{
		if(preg_match('/[^a-z_\-0-9]/i', $input)) {
			return false;
		}

		return true;
	}

}
