<?php

if (!defined('ABSPATH')) exit;

class tfrImporter extends tfrReader
{
    /**
     * The URL of the feed to examine and process
     * @var String
     */
    private $feedUrl;

    public function __construct($feedUrl)
    {
        $this->feedUrl = $feedUrl;
        parent::__construct($feedUrl);
    }

    /**
     * Get the post ID by searching for the GUID. 
     * @param  String       $guid       The GUID. This string starts with http://
     * @return Int                      The post ID if something is found, 0 when nothing's found
     */
    private function getIDfromGUID($guid)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid));
    }

    /**
     * Removes leading zeros and whitespace (except spaces). Converts the data to a string, if it wasn't already.
     * @param  String       $string     The string to cleanup
     * @return String
     */
    private function cleanupData($string)
    {
        $sanitized = str_replace('_', ' ', ltrim(trim((string)$string), '0'));
        if ($sanitized == '-  -' || substr($sanitized, 0, 3) == '-- ') {
            $sanitized = '';
        }

        return $sanitized;
    }

    /**
     * Adds a (newly created) post to a category. If the category doesn't exist, it's created
     * @param   Int       $postID         The ID of the post
     * @param   String    $categoryName   The name of the category. This method removes redundant whitespace.
     * @return  Boolean                   Returns true on success, false on failure.
     */
    private function addToCategory($postID, $categoryName)
    {   
        $categoryName = htmlentities($this->cleanupData($categoryName));
        $categoryID = get_cat_id($categoryName);

        if ($categoryID === 0) {
            wp_insert_term(trim($categoryName), 'category',
            array('description' => '', 'slug' => sanitize_title($categoryName)));
            $newCategoryID = get_cat_id($categoryName);
            
            if ($newCategoryID === 0) {
                return false;
            }

            wp_set_post_categories($postID, array($newCategoryID), true);
            return true;
        } else {
            wp_set_post_categories($postID, array($categoryID), true);
            return true;
        }
    }

    private function recursiveXMLLoop($input)
    {
        foreach ($input as $name => $value) {
            if (count($value) > 0) {
                $output[(string)$name] = $this->recursiveXMLLoop($value);
            } else {
                $output[(string)$name] = $this->cleanupData($value);
            }
        }

        return $output;
    }

    /**
     * The array used in wp_insert_post/wp_update_post, specifically for tfrlus.
     * @param  SimpleXMLElement     $vacancy    The default XML object
     * @param  Integer              $postID     If set, adds te ID field to the array for updating an existing post.
     * @return Array          
     */
    private function formatTfrlusPost($vacancy, $postID = null)
    {   
        $metaInputArray = $this->recursiveXMLLoop($vacancy);

        $metaInputArray['updated_at'] = date('Y-m-d H:i:s');
        $arr = array(
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_author'    => 1,
            'post_name'      => sanitize_title($this->cleanupData($vacancy->job_title)),
            'post_title'     => $this->cleanupData($vacancy->job_title),
            'post_content'   => $this->cleanupData($vacancy->job_description),
            'post_status'    => 'publish',
            'post_type'      => TFR_CPT,
            'post_date'      => date('Y-m-d H:i:s', strtotime($this->cleanupData($vacancy->created_at))),
            'guid'           => $this->cleanupData($vacancy->uid),
            'meta_input'     => array(TFR_META_KEY => $metaInputArray),
        );

        if ($postID !== null) {
            $arr['ID'] = $postID;
        }

        return $arr;
    }

    /**
     * Caller method to import the vacancies from the XML feed. Uses tfrReader zo get the XML object.
     * @return Array        An array containing the new, updated and total number of vacancies or errors.
     */
    public function importTfrlusVacancies()
    {
        tfrLogger::add('---- Starting update process for the Talentee feed. ----');
        $vacancies = $this->getObject();
        $new = 0; $updates = 0; $errors = 0; $lines = 0;

        foreach ($vacancies->vacancies->vacancy as $vacancy) {
            $lines++; $newPostID = 0;
            $postID = $this->getIDfromGUID('http://'.$this->cleanupData($vacancy->uid));

            if ($postID === null) {
                $newPostID = wp_insert_post($this->formatTfrlusPost($vacancy), true);
                if (is_wp_error($newPostID)) {
                    tfrLogger::add('ERROR: Failed to add resource. Possible duplicate resource or insertion failed. Wordpress says: '.$newPostID->get_error_message());
                    $errors++;
                    continue;
                } 

                if ($this->addToCategory($newPostID, $this->cleanupData($vacancy->category)) === false) {
                    tfrLogger::add("Warning: Failed to create a new category or add a post to a category. POSTID: $newPostID CATEGORYID: $vacancy->category");
                }

                $new++;
            } else {
                $updateArray = $this->formatTfrlusPost($vacancy, $postID);
                $oldData = get_post_meta($postID, TFR_META_KEY);
                $oldData = $oldData[0];
                $newData = $updateArray['meta_input'][TFR_META_KEY];

                foreach ($oldData as $name => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subName => $subValue) {
                            if (!empty($subValue) && empty($newData[$name][$subName])) {
                                $updateArray['meta_input'][TFR_META_KEY][$name][$subName] = $subValue;
                            }
                        }
                    } else {
                        if (!empty($value) && empty($newData[$name])) {
                            $updateArray['meta_input'][TFR_META_KEY][$name] = $value;
                        }
                    }
                }

                $update = wp_update_post($updateArray, true);

                if (is_wp_error($update)) {
                    tfrLogger::add('ERROR: An error occured while updating '.$vacancy->uid.'. Wordpress says: '.$update->get_error_message());
                    $errors++;
                    continue;
                }
                $updates++;
            }
        }

        tfrLogger::add("Notice: created $new new resources, updated $updates resources.");
        tfrLogger::add("Notice: encountered $errors errors, a total of $lines resources.");
        tfrLogger::add('---- Update process for the Talentee feed has finished. ----');

        $status = array(
            'totalLines' => $lines,
            'updates' => $updates,
            'new' => $new,
            'errors' => $errors
        );

        return $status; 
    }
}
