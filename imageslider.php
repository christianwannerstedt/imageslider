<?php
/**
 * @package Image Slider
 * @version 1.0
 */
/*
Plugin Name: ImageSlider
Description: A simple image slider
Author: Christian Wannerstedt @ Kloon Production AB
Version: 1.0
Author URI: http://www.kloon.se
*/


// ****************************** Installation / Uninstallation ******************************
register_activation_hook( __FILE__, 'imageslider_install' );
register_deactivation_hook( __FILE__, 'imageslider_uninstall' );

function imageslider_install(){
	global $wpdb;
	$structure = "CREATE TABLE ". $wpdb->prefix ."imsl_slide_shows (
	  `id` int(9) unsigned NOT NULL auto_increment,
	  `title` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
	  `transition_time` smallint(5) unsigned NOT NULL,
	  `easing` varchar(20) character set utf8 collate utf8_swedish_ci NOT NULL,
	  `width` smallint(5) unsigned NOT NULL,
	  `height` smallint(5) unsigned NOT NULL,
	  `created` datetime NOT NULL,
	  `updated` datetime NOT NULL,
	  PRIMARY KEY  (`id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci";
	$wpdb->query($structure);

	$structure = "CREATE TABLE ". $wpdb->prefix ."imsl_slides (
		`id` MEDIUMINT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`slideShowId` SMALLINT( 5 ) UNSIGNED NOT NULL ,
		`position` SMALLINT( 5 ) NOT NULL ,
		`filename` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
		`dirname` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
		`url` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
		`large_url` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
	  	`thumb_url` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
		`title` varchar(150) character set utf8 collate utf8_swedish_ci NOT NULL,
		`link` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
		`type` varchar(20) character set utf8 collate utf8_swedish_ci NOT NULL,
		`mime` varchar(20) character set utf8 collate utf8_swedish_ci NOT NULL,
		`width` smallint(5) unsigned NOT NULL,
		`height` smallint(5) unsigned NOT NULL,
	  	`thumb_width` smallint(4) unsigned NOT NULL,
	  	`thumb_height` smallint(4) unsigned NOT NULL,
		`created` DATETIME NOT NULL ,
		`updated` DATETIME NOT NULL ,
		INDEX ( `slideShowId` )
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci";
	$wpdb->query($structure);
}
function imageslider_uninstall(){
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS `". $wpdb->prefix ."imsl_slide_shows`;");
	$wpdb->query("DROP TABLE IF EXISTS `". $wpdb->prefix ."imsl_slides`;");
}




// ****************************** Admin page ******************************
add_action('admin_menu', 'imsl_menu');
function imsl_menu() {
	# Add the menu button
	$imageslider_index_page = add_menu_page( 'Manage slide shows', 'Slide shows', 'manage_options', 'imageslider-index', 'imageslider_index_view', '', 3);
	$imageslider_new_page = add_submenu_page( 'imageslider-index', 'New slide show', 'New slide show', 'manage_options', 'imageslider-new', 'imageslider_new_view');
	$imageslider_edit_slide_show_page = add_submenu_page( 'imageslider-edit-slide-show', 'Edit slide show', 'Edit slide show', 'manage_options', 'imageslider-edit-slide-show', 'imageslider_edit_slide_show_view');

	# Add css and js script links
	add_action( "admin_print_scripts-". $imageslider_edit_slide_show_page, 'imageslider_edit_slide_show_head' );
}



// ****************************** Admin index view ******************************
function imageslider_index_view() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	// Delete slide show
	if (isset($_POST["action"]) && $_POST["action"] == "delete" && isset($_POST["sid"]) && is_numeric($_POST["sid"])){
		global $wpdb;
		$slide_show_id = $_POST["sid"];
		$slide_show = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id='%d' LIMIT 1;", 
			$wpdb->prefix ."imsl_slide_shows",
			$slide_show_id
		));
		$slides = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE slideShowId='%d' ORDER BY position LIMIT 5;", 
			$wpdb->prefix ."imsl_slides",
			$slide_show_id
		));

		// Delete all slide images
		foreach ($slides as $slide){
			if (file_exists($slide->dirname .'/'. $slide->filename .'.'. $slide->type))
				unlink($slide->dirname .'/'. $slide->filename .'.'. $slide->type);
			if (file_exists($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type))
				unlink($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type);
			if (file_exists($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type))
				unlink($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type);
		}

		// Delete db records
		$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE slideShowId='%d';",
			$wpdb->prefix ."imsl_slides",
			$slide_show_id
		));
		$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE id='%d' LIMIT 1;",
			$wpdb->prefix ."imsl_slide_shows",
			$slide_show_id
		));	
	}
	

	wp_enqueue_style('imageslider_admin_style', plugins_url('css/admin.css', __FILE__));

	// Fetch slide shows and render view
	global $wpdb, $slide_shows;
	$slide_shows = $wpdb->get_results(sprintf("SELECT * FROM `%s` ORDER BY id;", $wpdb->prefix ."imsl_slide_shows"));
	foreach ($slide_shows as $slide_show){
		$slide_show->slides = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE slideShowId='%d' ORDER BY position LIMIT 5;", 
			$wpdb->prefix ."imsl_slides",
			$slide_show->id
		));
	}

	require_once(dirname(__FILE__) .'/admin_index.php');
}


// ****************************** Admin new view ******************************
function imageslider_new_view() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	if (isset($_POST["action"]) && $_POST["action"] == "create" && isset($_POST["slide_show_title"])){
		
		// Save new slide show
		global $wpdb;
		$wpdb->get_results(sprintf("INSERT INTO `%s` (title,transition_time,easing,width,height,updated,created) VALUES('%s',%d,'%s',%d,%d,NOW(),NOW());",
			$wpdb->prefix .'imsl_slide_shows',
			mysql_real_escape_string($_POST["slide_show_title"]),
			mysql_real_escape_string($_POST["slide_show_transition_time"]),
			mysql_real_escape_string($_POST["slide_show_easing"]),
			intval(mysql_real_escape_string($_POST["slide_show_width"])),
			intval(mysql_real_escape_string($_POST["slide_show_height"]))
		));
		
		imageslider_index_view();
	} else {

		wp_enqueue_script('jquery');
		wp_enqueue_script("jquery-effects-core");
		wp_enqueue_style('imageslider_admin_style', plugins_url('css/admin.css', __FILE__));
		require_once(dirname(__FILE__) .'/admin_new_slide_show.php');
	}
}


// ****************************** Update slide show settings (AJAX) ******************************
add_action('wp_ajax_update_slide_show_settings', 'update_slide_show_settings');
function update_slide_show_settings() {
	global $wpdb;

	if (isset($_POST['slide_show_id']) && is_numeric($_POST['slide_show_id'])){
		$slide_show_id = intval( $_POST['slide_show_id'] );

		$query = sprintf("UPDATE `%s` SET title='%s', transition_time='%s', easing='%s', updated=NOW() WHERE id='%d' LIMIT 1;",
			$wpdb->prefix ."imsl_slide_shows",
            mysql_real_escape_string($_POST["title"]),
            intval(mysql_real_escape_string($_POST["transition_time"])),
            mysql_real_escape_string($_POST["easing"]),
            $slide_show_id);

		$wpdb->get_results($query);

    	echo 200;
	} else {
		echo 500;
	}

	die(); // this is required to return a proper result
}


// ****************************** Admin edit slide showview ******************************
function imageslider_edit_slide_show_view() {
	global $wpdb, $slide_show, $slides;

	// Access and input validation
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	} else if (!isset($_GET["slide_show_id"]) || !is_numeric($_GET["slide_show_id"])){
		wp_die( __('Incorrect input data.') );
	}

	// Check if the slide show exists
	$slide_show_id = $_GET["slide_show_id"];
	$result = $wpdb->get_results("SELECT * FROM `". $wpdb->prefix ."imsl_slide_shows` WHERE id =". $slide_show_id ." LIMIT 1;");
	if (!$result[0]){
		wp_die( __('The specified slide show does not exist.') );
	}

	// Setup variables and render view
	$slide_show = $result[0];
	$slides = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix ."imsl_slides WHERE slideShowId=". $slide_show->id ." ORDER BY position;");

	require_once(dirname(__FILE__) .'/admin_edit_slide_show.php');
}
function imageslider_edit_slide_show_head() {
	// Add jquery
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-effects-core");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-widget");
	wp_enqueue_script("jquery-ui-mouse");
	wp_enqueue_script("jquery-ui-sortable");

	// Add js libs (backbone, underscore & Pluploader)
	wp_enqueue_script('underscore', plugins_url('js/libs/underscore-min.js', __FILE__));
	wp_enqueue_script('backbone', plugins_url('js/libs/backbone-min.js', __FILE__));
	wp_enqueue_script('plupload', plugins_url('js/libs/plupload/js/plupload.js', __FILE__));
	wp_enqueue_script('plupload-html5', plugins_url('js/libs/plupload/js/plupload.html5.js', __FILE__));
	wp_enqueue_script('plupload-flash', plugins_url('js/libs/plupload/js/plupload.flash.js', __FILE__));
	wp_enqueue_script('plupload-html4', plugins_url('js/libs/plupload/js/plupload.html4.js', __FILE__));

	// Add own scripts
	wp_enqueue_script('imsl-pluploader', plugins_url('js/pluploader.js', __FILE__));
	wp_enqueue_script('imsl-edit-slide-show', plugins_url('js/admin_edit_slide_show.js', __FILE__));

	// CSS
	wp_enqueue_style('imageslider_admin_style', plugins_url('css/admin.css', __FILE__));
	wp_enqueue_style('swfupload_style', plugins_url('js/swfupload/css/default.css', __FILE__));
}


// ****************************** Delete slide (AJAX) ******************************
add_action('wp_ajax_delete_slide', 'delete_slide_callback');
function delete_slide_callback() {
	global $wpdb;

	if (isset($_POST['slide_id']) && is_numeric($_POST['slide_id'])){
		$slide_id = intval( $_POST['slide_id'] );
		$table = $wpdb->prefix ."imsl_slides";

		// Delete image files
		$result = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id=%d LIMIT 1;",
			$table,
			$slide_id
		));
		if ($result){
			foreach ($result as $slide){
				if (file_exists($slide->dirname .'/'. $slide->filename .'.'. $slide->type))
					unlink($slide->dirname .'/'. $slide->filename .'.'. $slide->type);
				if (file_exists($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type))
					unlink($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type);
				if (file_exists($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type))
					unlink($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type);
			}

			// Delete record
			$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE id='%d' LIMIT 1;",
				$table,
				$slide_id
			));	
		}

    	echo 200;
	} else {
		echo 500;
	}

	die();
}


// ****************************** Update slide (AJAX) ******************************
add_action('wp_ajax_update_slide', 'update_slide_callback');
function update_slide_callback() {
	global $wpdb;

	if (isset($_POST['slide_id']) && is_numeric($_POST['slide_id'])){
		
		$wpdb->get_results(sprintf("UPDATE  `%s` SET title='%s', link='%s', updated=NOW() WHERE id='%d' LIMIT 1;",
			$wpdb->prefix ."imsl_slides",
            mysql_real_escape_string($_POST["title"]),
            mysql_real_escape_string($_POST["link"]),
            intval( $_POST['slide_id'] )
        ));
    	echo 200;

	} else {
		echo 500;
	}

	die();
}


// ****************************** Update slide position (AJAX) ******************************
add_action('wp_ajax_update_slide_position', 'update_slide_position_callback');
function update_slide_position_callback() {
	global $wpdb;

	if (isset($_POST["ids"])){

		$ids = $_POST["ids"];
		$arrIds = explode(",",  $ids);
		$position = 0;
		foreach ($arrIds as $id) {
			$wpdb->get_results(sprintf("UPDATE  `%s` SET position='%d' WHERE id='%d' LIMIT 1;",
				$wpdb->prefix ."imsl_slides",
				$position,
            	$id
            ));
			$position++;
		}

		echo 200;

	} else {
		echo 500;
	}

	die();
}


// ****************************** Show slide shows in content ******************************
add_action ('the_content', 'imageslider_the_content');
function imageslider_the_content($content){
	global $wpdb;

	$pattern = '/\[SLIDE_SHOW_(\d)+\]/';
	preg_match($pattern, $content, $matches);

	if ($matches[0]){
		$slide_show_html = "";

		// Get the slide show
		$id = $matches[1];
		$result = $wpdb->get_results("SELECT * FROM `". $wpdb->prefix ."imsl_slide_shows` WHERE id =". $id ." LIMIT 1;");
		if ($result[0]){
			$slide_show = $result[0];

			// Added necessary js and css files
			wp_enqueue_script('jquery');
			wp_enqueue_script("jquery-effects-core");
			wp_enqueue_script('imageslider_slideshow', plugins_url('js/imageslide_slideshow.js', __FILE__));
        	wp_enqueue_style('imageslider_style', plugins_url('css/imageslider.css', __FILE__));

			// Get the slides
			$slides = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix ."imsl_slides WHERE slideShowId=". $slide_show->id ." ORDER BY position;");

			// Construct output
			$slide_show_html .= '<div class="imsl-slide-show" style="width: '. $slide_show->width .'px; height: '. $slide_show->height .'px;" data-slide-width="'. $slide_show->width .'" data-slide-height="'. $slide_show->height .'" data-transition-time="'. $slide_show->transition_time.'" data-easing="'. $slide_show->easing .'">';
			$slide_show_html .= '<ul>';
			$first = true;
			foreach ($slides as $slide){
				if ($first){
					$left = 0;
					$first = false;
				} else {
					$left = $slide_show->width;
				}
				$slide_show_html .= '
				<li id="slide-'. $slide->id .'" class="imsl-slide" style="left: '. $left .'px;">
					<img src="'. $slide->large_url .'">
				</li>';
			}
			$slide_show_html .= '</ul>';
			$slide_show_html .= '<img src="'. plugins_url('images/arrow-left.png', __FILE__) .'" class="imsl-arrow imsl-arrow-left">';
			$slide_show_html .= '<img src="'. plugins_url('images/arrow-right.png', __FILE__) .'" class="imsl-arrow imsl-arrow-right">';

			// Contrsuct dots
			$slide_show_html .= '<ul class="imsl-dots" style="margin-left: -'. (sizeof($slides) * 4 + (sizeof($slides) - 1) * 5) .'px">';
			$first = true;
			$position = 0;
			foreach ($slides as $slide){
				$slide_show_html .= '<li id="imsl-dot-'. $slide->id .'" data-slide-position="'. $position++ .'"';
				if ($first){
					$slide_show_html .= ' class="active"';
					$first = false;
				}
				$slide_show_html .= '></li>';
			}
			$slide_show_html .= '</ul>';

			$slide_show_html .= '</div>';


		}

		$content = str_replace($matches[0], $slide_show_html, $content);

	}

	return $content;
}
?>