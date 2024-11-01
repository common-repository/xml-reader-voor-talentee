<?php

class tfrUpdate {

	public function __construct()
	{
		require_once(TFR_PLUGIN_DIR.'/app/models/tfrReader.php');
		require_once(TFR_PLUGIN_DIR.'/app/models/tfrImporter.php');
	}

	public function update($type)
	{
		switch ($type) {
			case 'manual':
				return $this->manual();
				break;

			case 'scheduled':
				return $this->scheduled();
				break;
			
			default:
				return array('error' => 'Unknown command');
				break;
		}
	}

	private function processUpdate()
	{
		$feedUrl = tfrSettings::get('feedUrl');
		if ($feedUrl == false || $feedUrl == '') {
			return 'error';
		}

		$import = new tfrImporter($feedUrl);
		$status = $import->importTfrlusVacancies();

		return json_encode($status);
	}

	private function manual()
	{
		tfrLogger::add('Notice: A manual update was issued.');
		return $this->processUpdate();
	}

	private function scheduled()
	{
		tfrLogger::add('Notice: A scheduled update was issued');
		return $this->processUpdate();
	}
}
