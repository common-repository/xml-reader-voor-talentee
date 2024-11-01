<?php

if (!defined('ABSPATH')) exit;

class tfrLogger
{
    /**
     * Initialises the Logging library by creating a new log file if it does not exist
     * @return Void
     */
    public static function init()
    {
        if (!file_exists(TFR_PLUGIN_DIR.'/assets/logs/log.txt')) {
            file_put_contents(TFR_PLUGIN_DIR.'/assets/logs/log.txt', date('Y-m-d H:i:s')." - Log created\n");
        }
    }

    /**
     * Add a line to the logfile. Creates a new logfile when it contains more than 250 lines.
     * @param String    $contents   The information to add to the logfile
     */
    public static function add($contents)
    {
        $logFile = TFR_PLUGIN_DIR.'/assets/logs/log.txt';

        $linecount = 0;
        $handle = fopen($logFile, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            $linecount++;
        }
        fclose($handle);

        if ($linecount > 2000) {
            rename($logFile, TFR_PLUGIN_DIR.'/assets/logs/'.time().' - logbackup.txt');
            file_put_contents($logFile, 'Notice: '.get_date_from_gmt(date('Y-m-d H:i:s'))." - Log created\n");
        }

        $current = file_get_contents($logFile);
        $current .= get_date_from_gmt(date('Y-m-d H:i:s')).' - '.$contents."\n";
        file_put_contents($logFile, $current);
    }

    /**
     * Loads the logfile to memory and splits it to an array
     * @return Array
     */
    public static function logToArray()
    {
        $logFile = TFR_PLUGIN_DIR.'/assets/logs/log.txt';

        $textFile = file_get_contents($logFile);
        $arrayFile = explode("\n", $textFile);

        return $arrayFile;
    }

    /**
     * List all logfiles.
     * @return Array
     */
    public static function findAllLogs()
    {
        $logDir = TFR_PLUGIN_DIR.'/assets/logs/';
        return glob($logDir.'*.txt');
    }
}
