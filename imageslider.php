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


require_once(dirname(__FILE__) .'/lib/utils.php');
define('IMSL_TABLE_SLIDE_SHOWS', "wp_imsl_slide_shows");
define('IMSL_TABLE_SLIDES', "wp_imsl_slides");



// ****************************** Installation / Uninstallation ******************************
register_activation_hook( __FILE__, 'imageslider_install' );
register_deactivation_hook( __FILE__, 'imageslider_uninstall' );

function imageslider_install(){
	global $wpdb;
	$structure = "CREATE TABLE ". IMSL_TABLE_SLIDE_SHOWS ." (
	  `id` int(9) unsigned NOT NULL auto_increment,
	  `title` varchar(255) character set utf8 collate utf8_swedish_ci NOT NULL,
	  `transition_time` smallint(5) unsigned NOT NULL,
	  `easing` varchar(20) character set utf8 collate utf8_swedish_ci NOT NULL,
	  `display_title` tinyint(1) unsigned NOT NULL DEFAULT '0',
	  `theme` varchar(20) character set utf8 collate utf8_swedish_ci NOT NULL,
	  `width` smallint(5) unsigned NOT NULL,
	  `height` smallint(5) unsigned NOT NULL,
	  `created` datetime NOT NULL,
	  `updated` datetime NOT NULL,
	  PRIMARY KEY  (`id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci";
	$wpdb->query($structure);

	$structure = "CREATE TABLE ". IMSL_TABLE_SLIDES ." (
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
	$wpdb->query("DROP TABLE IF EXISTS `". IMSL_TABLE_SLIDE_SHOWS ."`;");
	$wpdb->query("DROP TABLE IF EXISTS `". IMSL_TABLE_SLIDES ."`;");
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
	imsl_assert_admin_access();

	// Delete slide show
	if (imsl_is_action("delete") && isset($_POST["sid"]) && is_numeric($_POST["sid"])){
		global $wpdb;
		$slide_show_id = $_POST["sid"];
		$slide_show = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id='%d' LIMIT 1;", IMSL_TABLE_SLIDE_SHOWS, $slide_show_id));
		$slides = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE slideShowId='%d' ORDER BY position LIMIT 5;", IMSL_TABLE_SLIDES, $slide_show_id));

		// Delete all slide images
		foreach ($slides as $slide){
			imsl_delete_file($slide->dirname .'/'. $slide->filename .'.'. $slide->type);
			imsl_delete_file($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type);
			imsl_delete_file($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type);
		}

		// Delete db records
		$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE slideShowId='%d';", IMSL_TABLE_SLIDES, $slide_show_id));
		$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE id='%d' LIMIT 1;", IMSL_TABLE_SLIDE_SHOWS, $slide_show_id));
	}


	wp_enqueue_style('imageslider_admin_style', plugins_url('css/admin.css', __FILE__));

	// Fetch slide shows and render view
	global $wpdb, $slide_shows;
	$slide_shows = $wpdb->get_results(sprintf("SELECT * FROM `%s` ORDER BY id;", IMSL_TABLE_SLIDE_SHOWS));
	foreach ($slide_shows as $slide_show){
		$slide_show->slides = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE slideShowId='%d' ORDER BY position LIMIT 5;", IMSL_TABLE_SLIDES, $slide_show->id));
	}

	require_once(dirname(__FILE__) .'/admin_index.php');
}


// ****************************** Admin new view ******************************
function imageslider_new_view() {
	imsl_assert_admin_access();

	if (imsl_is_action("create") && isset($_POST["slide_show_title"])){

		// Save new slide show
		global $wpdb;
		$wpdb->get_results(sprintf("INSERT INTO `%s` (title,transition_time,easing,theme,width,height,updated,created) VALUES('%s',%d,'%s','%s',%d,%d,NOW(),NOW());",
			$wpdb->prefix .'imsl_slide_shows',
			mysql_real_escape_string($_POST["slide_show_title"]),
			mysql_real_escape_string($_POST["slide_show_transition_time"]),
			mysql_real_escape_string($_POST["slide_show_easing"]),
			mysql_real_escape_string($_POST["slide-show-theme"]),
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
add_action('wp_ajax_update_slide_show_settings', 'imsl_update_slide_show_settings');
function imsl_update_slide_show_settings() {
	global $wpdb;

	if (isset($_POST['slide_show_id']) && is_numeric($_POST['slide_show_id'])){
		$slide_show_id = intval( $_POST['slide_show_id'] );

		$query = sprintf("UPDATE `%s` SET title='%s', transition_time='%s', easing='%s', theme='%s', display_title='%d', updated=NOW() WHERE id='%d' LIMIT 1;",
			IMSL_TABLE_SLIDE_SHOWS,
            mysql_real_escape_string($_POST["title"]),
            intval(mysql_real_escape_string($_POST["transition_time"])),
            mysql_real_escape_string($_POST["easing"]),
            mysql_real_escape_string($_POST["theme"]),
            $_POST["display_title"] == 1 ? 1 : 0,
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
	imsl_assert_admin_access();
	$slide_show_id = imsl_assert_numeric_get("slide_show_id");

	// Check if the slide show exists
	$result = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id=%d LIMIT 1;", IMSL_TABLE_SLIDE_SHOWS, $slide_show_id));
	if (!$result[0]){
		wp_die( __('The specified slide show does not exist.') );
	}

	// Setup variables and render view
	$slide_show = $result[0];
	$slides = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE slideShowId=%d ORDER BY position;", IMSL_TABLE_SLIDES, $slide_show->id));

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
add_action('wp_ajax_delete_slide', 'imsl_delete_slide_callback');
function imsl_delete_slide_callback() {
	imsl_assert_admin_access();

	if (isset($_POST['slide_id']) && is_numeric($_POST['slide_id'])){
		global $wpdb;
		$slide_id = intval( $_POST['slide_id'] );

		// Delete image files
		$result = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id=%d LIMIT 1;", IMSL_TABLE_SLIDES, $slide_id));
		if ($result){
			foreach ($result as $slide){
				imsl_delete_file($slide->dirname .'/'. $slide->filename .'.'. $slide->type);
				imsl_delete_file($slide->dirname .'/'. $slide->filename .'-thumb.'. $slide->type);
				imsl_delete_file($slide->dirname .'/'. $slide->filename .'-large.'. $slide->type);
			}

			// Delete record
			$wpdb->get_results(sprintf("DELETE FROM  `%s` WHERE id='%d' LIMIT 1;", IMSL_TABLE_SLIDES, $slide_id));
		}

    	echo 200;
	} else {
		echo 500;
	}

	die();
}


// ****************************** Update slide (AJAX) ******************************
add_action('wp_ajax_update_slide', 'imsl_update_slide_callback');
function imsl_update_slide_callback() {
	global $wpdb;

	if (isset($_POST['slide_id']) && is_numeric($_POST['slide_id'])){

		$wpdb->get_results(sprintf("UPDATE  `%s` SET title='%s', link='%s', updated=NOW() WHERE id='%d' LIMIT 1;",
			IMSL_TABLE_SLIDES,
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
add_action('wp_ajax_update_slide_position', 'imsl_update_slide_position_callback');
function imsl_update_slide_position_callback() {
	global $wpdb;

	if (isset($_POST["ids"])){

		$ids = $_POST["ids"];
		$arrIds = explode(",",  $ids);
		$position = 0;
		foreach ($arrIds as $id) {
			$wpdb->get_results(sprintf("UPDATE  `%s` SET position='%d' WHERE id='%d' LIMIT 1;",
				IMSL_TABLE_SLIDES,
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
// ****************************** Get single slide show (AJAX) ******************************
add_action('wp_ajax_get_slideshow', 'get_slideshow_callback');
add_action('wp_ajax_nopriv_get_slideshow', 'get_slideshow_callback');
function get_slideshow_callback() {
    global $wpdb;
	if (isset($_GET["slide_show_id"]) && is_numeric($_GET["slide_show_id"])){
       $id = $_GET["slide_show_id"];
       $slide_show_html = imageslider_get_slideshow_output_for_id( $id );
       echo json_encode(array(
           'status' => 200,
           'id' => $id,
           'html' => $slide_show_html
       ));

	} else {
	       echo json_encode(array('status' => 500));
	}

	die();
};

add_action('the_content', 'imsl_imageslider_the_content');
function imsl_imageslider_the_content($content){
	$pattern = '/\[SLIDE_SHOW_(\d+)\]/';
	preg_match($pattern, $content, $matches);

	if ($matches[0]){
		$slide_show_html = "";

		// Get the slide show
		$id = $matches[1];
		$slide_show_html .= imageslider_get_slideshow_output_for_id($id);
		$content = str_replace($matches[0], $slide_show_html, $content);

	}

	return $content;
}
function imageslider_get_slideshow_output_for_id($id){
	global $wpdb;

	$slide_show_html = "";
	$result = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id=%d LIMIT 1;", IMSL_TABLE_SLIDE_SHOWS, $id));
	if ($result[0]){
		$slide_show = $result[0];
		$image_dir = plugins_url('themes/'. $slide_show->theme .'/img/', __FILE__);

		// Added necessary js and css files
		wp_enqueue_script('jquery');
		wp_enqueue_script("jquery-effects-core");
		wp_enqueue_script('imageslider_slideshow', plugins_url('js/imageslide_slideshow.js', __FILE__));
		wp_enqueue_style('imageslider_style', plugins_url('css/imageslider.css', __FILE__));

		// Get the slides
		$slides = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE slideShowId=%d ORDER BY position;", IMSL_TABLE_SLIDES, $slide_show->id));

		// Construct output
		$slide_show_html .= '<div class="imsl-slide-show theme-'. $slide_show->theme .'" style="width: '. $slide_show->width .'px; height: '. $slide_show->height .'px;" data-slide-width="'. $slide_show->width .'" data-slide-height="'. $slide_show->height .'" data-transition-time="'. $slide_show->transition_time.'" data-easing="'. $slide_show->easing .'" data-slideshow="'. $slide_show->id .'"">';
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
			<li id="slide-'. $slide->id .'" class="imsl-slide" style="left: '. $left .'px;">';

			if ($slide_show->display_title && $slide->title){
				$slide_show_html .= '
					<div class="imsl-slide-show-title"><span>'. $slide->title .'</span></div>';
			}

			if ($slide->link){
				$slide_show_html .= '<a href="'. $slide->link .'">';
			}

			$slide_show_html .= '
				<img src="'. $slide->large_url .'">';

			if ($slide->link){
				$slide_show_html .= '</a>';
			}

			$slide_show_html .= '
			</li>';
		}
		$slide_show_html .= '</ul>';
		$slide_show_html .= '<img src="'. $image_dir .'arrow-left.png" class="imsl-arrow imsl-arrow-left">';
		$slide_show_html .= '<img src="'. $image_dir .'arrow-right.png" class="imsl-arrow imsl-arrow-right">';

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
	return $slide_show_html;
}

function imageslider_head_scripts(){
	wp_enqueue_script('jquery');
    wp_enqueue_script("jquery-effects-core");
    wp_enqueue_script('imageslider_slideshow', plugins_url('js/imageslide_slideshow.js', __FILE__));
    wp_enqueue_style('imageslider_style', plugins_url('css/imageslider.css', __FILE__));
}

function imageslider_get_slideshow($id){
	global $wpdb;
	$result = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE id=%d LIMIT 1;", IMSL_TABLE_SLIDE_SHOWS, $id));
	return $result[0];
}

?>