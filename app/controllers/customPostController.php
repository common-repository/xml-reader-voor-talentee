<?php
if (!defined('ABSPATH')) exit;

class tfrCustomPostController
{

	public function register()
	{
		$this->postTypeSetup();
		add_filter('single_template', array($this, 'singleTemplate'));
	    add_filter('archive_template', array($this, 'archiveTemplate'), 99);
	    add_action('deleteOnExpiryEvent', array($this, 'deleteOnExpiry'));
	    add_filter('pre_get_posts', array($this, 'addCategorySupport'));
		add_action('admin_menu', array($this, 'removeSubmenuTabs'), 999);
	}

	public function removeSubmenuTabs() {
		remove_submenu_page( 'edit.php?post_type=tfr_vacancies', 'post-new.php?post_type=tfr_vacancies' );
		remove_submenu_page( 'edit.php?post_type=tfr_vacancies', 'edit-tags.php?taxonomy=category&amp;post_type=tfr_vacancies' );
	}

	private function postTypeSetup()
	{
		$labels = array(
			'name'                  => _x('Vacancies', 'Post Type General Name', 'talentee-feedreader'),
			'singular_name'         => _x('Vacancy', 'Post Type Singular Name', 'talentee-feedreader'),
			'menu_name'             => __('Vacancies', 'talentee-feedreader'),
			'name_admin_bar'        => __('Vacancy', 'talentee-feedreader'),
			'archives'              => __('Item Archives', 'talentee-feedreader'),
			'parent_item_colon'     => __('Parent Item:', 'talentee-feedreader'),
			'all_items'             => __('All Items', 'talentee-feedreader'),
			'add_new_item'          => __('Add New Item', 'talentee-feedreader'),
			'add_new'               => __('New vacancy', 'talentee-feedreader'),
			'new_item'              => __('New vacancy', 'talentee-feedreader'),
			'edit_item'             => __('Edit vacacy', 'talentee-feedreader'),
			'update_item'           => __('Update vacancy', 'talentee-feedreader'),
			'view_item'             => __('View vacancy', 'talentee-feedreader'),
			'search_items'          => __('Search vacancies', 'talentee-feedreader'),
			'not_found'             => __('Not found', 'talentee-feedreader'),
			'not_found_in_trash'    => __('Not found in Trash', 'talentee-feedreader'),
			'featured_image'        => __('Featured Image', 'talentee-feedreader'),
			'set_featured_image'    => __('Set featured image', 'talentee-feedreader'),
			'remove_featured_image' => __('Remove featured image', 'talentee-feedreader'),
			'use_featured_image'    => __('Use as featured image', 'talentee-feedreader'),
			'insert_into_item'      => __('Insert into item', 'talentee-feedreader'),
			'uploaded_to_this_item' => __('Uploaded to this item', 'talentee-feedreader'),
			'items_list'            => __('Items list', 'talentee-feedreader'),
			'items_list_navigation' => __('Items list navigation', 'talentee-feedreader'),
			'filter_items_list'     => __('Filter items list', 'talentee-feedreader'),
		);

		$args = array(
			'label'                 => __('Vacancy', 'talentee-feedreader'),
			'description'           => __('Vacature lezer voor Talentee', 'talentee-feedreader'),
			'labels'                => $labels,
			'supports'              => array('title', 'editor', 'excerpt', 'custom-fields',),
			'taxonomies'            => array('category', 'post_tag'),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-welcome-widgets-menus',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,		
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
			'rewrite'				=> array('slug' => tfrSettings::get('cptKeyword'), 'with_front' => false),
		);
		register_post_type(TFR_CPT, $args);
	}

	public function deleteOnExpiry()
	{
		tfrLogger::add('Notice: Starting hook to delete expired posts.');
		if (tfrSettings::get('deleteOnExpiry') && post_type_exists(TFR_CPT)) {
			$args = array (
				'post_type'              => array(TFR_CPT),
				'post_status'            => array('publish'),
				'nopaging'               => true,
				'posts_per_page'         => -1,
				'ignore_sticky_posts'    => false,
			);
			$getAllCPT = new WP_Query($args);

			if ($getAllCPT->have_posts()) {
				while ($getAllCPT->have_posts()) {
					$getAllCPT->the_post();
					global $post;
					$meta = get_post_meta($post->ID, TFR_META_KEY);

					if (isset($meta[0]['expires_at']) && !empty($meta[0]['expires_at'])) {
						$expiryDate = $meta[0]['expires_at'];	
					} else {
						tfrLogger::add('Notice: Tried to find the expirydate for post '.$post->ID.', but no data was found.');
						continue;
					}

					if ($expiryDate > date('Y-m-d H:i:s')) {
						continue;
					} else {
						if (wp_delete_post($post->ID, true) === false) {
							tfrLogger::add('Warning: Tried to delete post '.$post->ID.' because the expirydate was reached, but it failed.');
						} else {
							tfrLogger::add('Notice: Deleted post '.$post->ID.' because the expirydate was reached.');
						}
					}
				}
			}
			wp_reset_postdata();
		} else {
			tfrLogger::add('Debug: Delete on Expiry hook was fired, but failed due to failing settings.');
		}
		tfrLogger::add('Notice: Expired posts cleanup has finished.');
	}

	public function singleTemplate($single) 
	{
	    global $wp_query, $post;

	    if ($post->post_type == TFR_CPT) {
	        if (file_exists(TFR_PLUGIN_DIR. '/templates/single.php')) {
	            return TFR_PLUGIN_DIR . '/templates/single.php';
	        }
	    }

	    return $single;
	}

	public function archiveTemplate($archive_template)
	{
	    global $post;

	    if (is_post_type_archive (TFR_CPT)) {
            return TFR_PLUGIN_DIR.'/templates/archive.php';
	    }

	    return $archive_template;
	}

	public function addCategorySupport($query) {
		if(is_category() || is_tag()) {
			$post_type = get_query_var('post_type');
			if($post_type) {
				$post_type = $post_type;
			} else {
				$post_type = array('post',TFR_CPT);
			}
			$query->set('post_type',$post_type);
			return $query;
		}
	}

	public function printMetaToInput($meta, $parents = array(), $hideIDs = true) {
		if (count($parents) > 0 && is_array($meta)) {
			foreach ($meta as $key => $name) {
				$parentsAdd = $parents;
				$parentsAdd[] = $key;
				$this->printMetaToInput($name, $parentsAdd, $hideIDs);
			}
		} elseif (count($parents) <= 0 && is_array($meta)) {
			foreach ($meta as $key => $name) {
				$this->printMetaToInput($name, array($key), $hideIDs);
			}
		} elseif (count($parents) && !is_array($meta)) {
			$names = '';
			foreach ($parents as $parent) {
				$names .= '['.$parent.']';
			}
			if ($hideIDs && $parent == 'id') {
				echo '<input type="hidden" name="tfrForm'.$names.'" value="'.$meta.'" />';
			} else {
				echo '<div class="column column-50"><label>'.str_replace('_', ' ', ucfirst(implode(' - ', $parents))).'</label><input class="widefat" type="text" name="tfrForm'.$names.'" value="'.$meta.'" /></div>';
			}
			$parents = array();
		} else {
			if ($hideIDs && $parents == 'id') {
				echo '<input type="hidden" name="tfrForm['.$parents.']" value="'.$meta.'" />';
			} else {
				echo '<div class="column column-50"><label>'.str_replace('_', ' ', ucfirst($parents)).'</label><input class="widefat" type="text" name="tfrForm['.$parents.']" value="'.$meta.'" /></div>';
			}
			
		}
	}

	private function recursiveMetaPrint($meta, $parents = array(), $glue) {
		$output = '';
        if (count($parents) > 0 && is_array($meta)) {
			foreach ($meta as $key => $name) {
				array_push($parents, $key);
				$output .= $this->recursiveMetaPrint($name, $parents, $glue);
			}
		} elseif (count($parents) <= 0 && is_array($meta)) {
			foreach ($meta as $key => $name) {
				$output .= $this->recursiveMetaPrint($name, array($key), $glue);
			}
		} elseif (count($parents) && !is_array($meta)) {
			$ids = implode('-', $parents);
			$class = implode(' ', $parents);
			$output .= '<div id="'.$ids.'" class="'.$class.' vacancy-name">'.implode($glue, $parents).'</div><div id="'.$ids.'-value" class="'.$class.' vacancy-value">'.$meta.'</div>';
			$parents = array();
		} else {
			if ($parents == 'id') { return; }
			$output .= '<div id="'.$parents.'" class="'.$parents.' vacancy-name">'.$parents.'</div><div id="'.$parents.'" class="vacancy-value">'.$meta.'</div>';
		}

		return $output;
	}

	public function printMetaRaw($postID, $field = null, $glue = ' | ') {
		$vacancyData = get_post_meta($postID, TFR_META_KEY);
        $vacancyData = $vacancyData[0]; //To support PHP 5.3
        $output = false;

        if ($field === null) {
        	foreach ($vacancyData as $key => $value) {
	        	$output .= $this->recursiveMetaPrint($value, array($key), $glue);
	        }
        } elseif (isset($vacancyData[$field])) {
        	foreach ($vacancyData[$field] as $key => $value) {
	        	$output .= $this->recursiveMetaPrint($value, array($key), $glue);
	        }
        }

        return $output;
	}

}