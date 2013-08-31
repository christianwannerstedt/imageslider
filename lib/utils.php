<?php
function imsl_assert_admin_access(){
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
}

function imsl_assert_numeric_get($key){
	if (!isset($_GET[$key]) || !is_numeric($_GET[$key])){
		wp_die( __('Incorrect indata.') );
	}
	return $_GET[$key];
}

function imsl_assert_numeric_post($key){
	if (!isset($_POST[$key]) || !is_numeric($_POST[$key])){
		wp_die( __('Incorrect indata.') );
	}
	return $_POST[$key];
}

function imsl_is_action($action){
	return (isset($_POST['admin-action']) && $_POST['admin-action'] == $action);
}

function imsl_delete_file($filename){
	if (file_exists($filename)) unlink($filename);
}