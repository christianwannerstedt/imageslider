<?php
global $screen_layout_columns, $slide_show, $slides;

add_meta_box("imageslider_content_slides", "Edit slides", "imsl_edit_meta_box", "imageslider_edit", "normal", "core");
add_meta_box("imageslider_content_upload", "Add new slides", "imsl_upload_meta_box", "imageslider_upload", "normal", "core");
add_meta_box("imageslider_content_settings", "Settings", "imsl_settings_meta_box", "imageslider_settings", "normal", "core");


// ****************************** Structure ******************************
?>
<script type="text/javascript">
var slide_show_id = <?php echo $slide_show->id; ?>,
	image_width = <?php echo $slide_show->width; ?>,
	image_height = <?php echo $slide_show->height; ?>,
	slides_json = <?php echo json_encode($slides); ?>;
</script>

<div class="wrap">
	<div class="metabox-holder">
		<?php do_meta_boxes('imageslider_edit','normal', null); ?>
	</div>

	<div class="metabox-holder">
		<?php do_meta_boxes('imageslider_upload','normal', null); ?>
	</div>

	<div class="metabox-holder">
		<?php do_meta_boxes('imageslider_settings','normal', null); ?>
	</div>
</div>



<?php
// ****************************** Edit box ******************************
function imsl_edit_meta_box(){
global $wpdb, $slides;
?>

<!-- Templates -->
<script type="text/template" id="slides-template">
	<ul id="edit-slides-list">
    <% var inner_template = _.template(jQuery('#slide-template') .html()) %>
    <% _.each(slides, function(slide){ %>
    	<%= inner_template(slide) %>
    <% }); %>
    </ul>
</script>
<script type="text/template" id="slide-template">
	<li id="slide-<%= id %>" class="clearfix" data-large-url="<%= large_url %>">
		<img src="<%= thumb_url %>" class="thumb">
		<ul class="edit-slide-info">
			<li>
				<label for="slide-<%= id %>-title">Title: </label>
				<input id="slide-<%= id %>-title" type="text" value="<%= title %>">
			</li>
			<li>
				<label for="slide-<%= id %>-link">Link: </label>
				<input id="slide-<%= id %>-link" type="text" value="<%= link %>">
			</li>
		</ul>
		<p>
			<input type="button" id="update-slide-<%= id %>" class="update-slide-button button-primary" value="Update" />
			<input type="button" id="delete-slide-<%= id %>" class="delete-slide-button button-secondary" value="Delete slide" />
		</p>
		<p id="slide-<%= id %>-updated" class="slide-updated">Slide updated</p>
	</li>
</script>


	<div id="edit-slides-list-container"></div>

<?php
}



// ****************************** Upload box ******************************
function imsl_upload_meta_box(){
global $wpdb, $slide_show;
?>

	<!-- Plupload -->
	<input type="hidden" id="plupload-flash-url" value="<?php echo get_option('home') .'/wp-content/plugins/'. dirname(plugin_basename(__FILE__)); ?>/js/libs/plupload/js/plupload.flash.swf">
	<input type="hidden" id="plupload-upload-path" value="<?php echo get_option('home') .'/wp-content/plugins/'. dirname(plugin_basename(__FILE__)); ?>/upload.php">

	<div id="plupload-container">
		<p>Drop images here, or <a id="plupload-browse-button">browse</a></p>
	</div>

	<ul id="plupload-list"></ul>

	<button id="plupload-submit-button" class="button-primary">Start upload</button>

<?php
}



// ****************************** Settings box ******************************
function imsl_settings_meta_box(){
global $wpdb, $slide_show;
?>

	<form id="form-update-imsl-settings" action="#" method="post" enctype="multipart/form-data">
		<input type="hidden" id="slide-show-id" value="<?php echo $slide_show->id; ?>">
		<ul class="imsl-ul-settings">
			<li>
				<label>Title:</label>
				<input type="text" id="slide-show-title" value="<?php echo $slide_show->title; ?>">
			</li>
			<li>
				<label>Transition time:</label>
				<input type="text" id="slide-show-transition-time" value="<?php echo $slide_show->transition_time; ?>">
			</li>
			<li>
				<label>Easing:</label>
				<select id="slide-show-easing" name="slide-show-easing">
					<option value="<?php echo $slide_show->easing; ?>" selected="selected"><?php echo $slide_show->easing; ?></option>
				</select>
			</li>
			<li>
				<label>Theme:</label>
				<select id="slide-show-theme" name="slide-show-theme">
					<option value="basic"<?php selected($slide_show->theme, "basic"); ?>>Basic</option>
					<option value="light"<?php selected($slide_show->theme, "light"); ?>>Light</option>
				</select>
			</li>
			<li>
				<label>Display slide titles:</label>
				<input type="checkbox" id="slide-show-display-title" name="slide-show-display-title" value="1"<?php if ($slide_show->display_title) echo " checked='checked'"; ?>>
			</li>
		</ul>

		<input type="submit" class="button-primary" id="imsl-update-settings-submit" value="Update" />
		<span id="imsl-update-settings-saving" style="display:none;">Saving, please wait..</span>
	</form>


<?php
}
?>