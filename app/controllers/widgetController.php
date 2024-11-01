<?php 
if (!defined('ABSPATH')) exit;

class tfrWidget extends WP_Widget {

    public function __construct()
    {
        parent::__construct(false, $name = 'Talentee Widget');
    }

    public function form($instance)
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $maxPosts = isset($instance['maxPosts']) ? esc_attr($instance['maxPosts']) : '';
        $maxLength = isset($instance['maxLength']) ? esc_attr($instance['maxLength']) : '';
        $fields = isset($instance['fields']) ? $instance['fields'] : array();

        $output = '
            <div class="row">
                <div class="column column-100">
                    <label for="'.$this->get_field_id('title').'">Widget titel</label>
                    <input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'" />
                </div>
            </div>
            <div class="row">
                <div class="column column-50">
                     <label for="'.$this->get_field_id('maxPosts').'">Maximaal aantal vacatures:</label>
                    <input class="widefat" id="'.$this->get_field_id('maxPosts').'" name="'.$this->get_field_name('maxPosts').'" type="text" value="'.$maxPosts.'" />
                </div>
                <div class="column column-50">
                     <label for="'.$this->get_field_id('maxPosts').'">Maximale veld lengte:</label>
                    <input class="widefat" id="'.$this->get_field_id('maxLength').'" name="'.$this->get_field_name('maxLength').'" type="text" value="'.$maxLength.'" />
                </div>
            </div>';

        $availableFields = array('omschrijving', 'vestiging', 'aanvraagdatum', 'startdatum', 'einddatum', 
            'postcode', 'locatie', 'functieomschrijving', 'gevraagd_word', 'geboden_word', 'brutoloon', 'brutoloonmax',
            'kenmerk', 'provincie', 'beroep', 'opleiding', 'naam', 'email');

        $checkedOutput = ''; $nonCheckedOutput = '';
        if (count($fields)) {
            $availableFields = array_diff($availableFields, $fields);

            foreach ($fields as $name => $value) {
                $checkedOutput .= ' 
                    <li class="column column-50 item">
                        <input id="'.$this->get_field_id('fields').$name.'" type="checkbox" name="'.$this->get_field_name('fields').'['.$name.']" value="'.$value.'" checked="checked"  />
                        <label for="'.$this->get_field_id('fields').$name.'">'.ucfirst($name).'</label>
                    </li>';
            }
        }

        foreach ($availableFields as $fieldName) {
            $nonCheckedOutput .= '
                <li class="column column-50 item">
                    <input id="'.$this->get_field_id('fields').$fieldName.'" type="checkbox" name="'.$this->get_field_name('fields').'['.$fieldName.']" value="'.$fieldName.'" />
                    <label for="'.$this->get_field_id('fields').$fieldName.'">'.ucfirst($fieldName).'</label>
                </li>';
        }

        $output .= '<ul class="row sortFieldList"><p>Velden:</p>';
        $output .= $checkedOutput;
        $output .= $nonCheckedOutput;
        $output .= '</ul>';
        $output .= '<script type="text/javascript" src="'.TFR_PLUGIN_URL.'assets/js/sortable.min.js"></script>';
        $output .= '<script type="text/javascript">
                        jQuery(document).ready(function(){
                            jQuery(".sortFieldList li").each(function(index, element) {
                                var numberAdjust = index - 17;
                                jQuery(element).find("span").remove();
                                jQuery(element).find("label").prepend("<span>"+numberAdjust+": </span>");
                            });

                            jQuery(".sortFieldList").srtbl().bind("sortupdate", function(e, ui) {
                                jQuery(".sortFieldList li").each(function(index, element) {
                                    var numberAdjust = index - 17;
                                    jQuery(element).find("span").remove();
                                    jQuery(element).find("label").prepend("<span>"+numberAdjust+": </span>");
                                });
                            });
                        ;})
                    </script>';

        echo $output;
    }


    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['title'] = strip_tags($new_instance['title']);
        $instance['maxPosts'] = strip_tags($new_instance['maxPosts']);
        $instance['maxLength'] = strip_tags($new_instance['maxLength']);
        $instance['fields'] = $new_instance['fields'];

        return $instance;
    }

    /**
     * Strip a string of it's tags and shorten it, if needed
     * @param  String     $string         The string to strip
     * @param  Integer    $length         The maximum length of $content.
     * @return String         
     */
    private function strip($string, $length)
    {
        if (strlen($string) > $length) {
            return substr(strip_tags($string), 0, $length-3).'... ';
        }

        return strip_tags($string);
    }

    /**
     * It's ugly, but it works..
     * @param  [type] $widgetSettings [description]
     * @param  [type] $data           [description]
     * @return [type]                 [description]
     */
    public function widget($widgetSettings, $data)
    {  
        $title = apply_filters('widget_title', $data['title']);
        $maxPosts = $data['maxPosts'];
        $maxLength = $data['maxLength'];
        $fields = $data['fields'];

        $queryArguments = array (
            'post_type'              => TFR_CPT,
            'post_status'            => 'publish',
            'nopaging'               => true,
            'posts_per_page'         => (int)$maxPosts,
            'ignore_sticky_posts'    => false,
            'order_by'               => 'date',
            'order'                  => 'DESC',
        );
        $getLatest = new WP_Query($queryArguments);

        $postStructure = ''; $i = 0;
        if ($getLatest->have_posts()) {
            while ($getLatest->have_posts()) {
                $getLatest->the_post(); global $post; $i++; 

                if ($i > $maxPosts) {
                    continue; 
                }

                $meta = get_post_meta($post->ID, TFR_META_KEY);
                $postStructure .= '<div class="widget-block">';
                foreach ($fields as $name => $value) {
                    $content = $this->strip($meta[0][$name], $maxLength);
                    if ($name == 'omschrijving') {
                        $postStructure .= '<h3 class="widget-field-'.$name.'"><a href="'.get_permalink($post->ID).'">'.$content.'</a></h3>';
                    } else {
                        $postStructure .= '<p class="widget-field-'.$name.'">'.$content.'</p>';
                    }
                }
                
                $postStructure .= '<p><a href="'.get_permalink($post->ID).'" title="'.ucfirst(get_the_title()).'">Lees verder</a></p>';
                $postStructure .= '</div>';
            }
        }
        wp_reset_postdata();

        extract($widgetSettings);
        $widgetOutput = $before_widget;
        $widgetOutput .= '<div class="widget-text wp_widget_plugin_box">';
        if (isset($title)) { $widgetOutput .= $before_title.$title.$after_title; }
        $widgetOutput .= $postStructure;
        $widgetOutput .= '<p><a href="'.get_post_type_archive_link(TFR_CPT).'" title="">Bekijk alle vacatures</a></p>';
        $widgetOutput .= '</div>';
        $widgetOutput .= $after_widget;

        echo $widgetOutput;
    }
}
