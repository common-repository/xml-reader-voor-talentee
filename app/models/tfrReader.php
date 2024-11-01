<?php

if (!defined('ABSPATH')) exit;

class tfrReader
{
    /**
     * Variable containing the XML feed URL.
     * @var String
     */
    private $feedUrl;
    
    public function __construct($feedUrl)
    {
        $this->feedUrl = $feedUrl;
    }

    /**
     * Private method which fetches the XML feed via cURL.
     * @return [type] [description]
     */
    private function loadFeed()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->feedUrl,
        ));

        $feed = curl_exec($curl);

        if (curl_error($curl) !== '') {
            tfrLogger::add('ERROR: Something went wrong while fetching the XML feed: '.$curl_error($curl));
            return false;
        }

        curl_close($curl);

        return $feed;
    }

    /**
     * Interpet the cURL result into an object. 
     * @return object SimpleXMLElement 
     */
    public function getObject()
    {
        $feed = $this->loadFeed();
        $object = simplexml_load_string($feed);

        if ($object === false) {
            tfrLogger::add('ERROR: Something went wrong while parsing the XML feed to an object.');
            return false;
        }

        return $object;
    }

    /**
     * Scaffold method to return an array
     * @return Array
     */
    public function getArray()
    {
        return false;
    }

    /**
     * Scaffold method to return the raw XML data
     * @return String
     */
    public function getRaw()
    {
        return false;
    }
    
}
