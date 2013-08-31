<?php
global $title, $screen_layout_columns;
add_meta_box("imageslider_content", $title, "imageslider_meta_box", "imageslider_new", "normal", "core");
?>

<div class="wrap">

	<div id="imageslider-index-container" class="metabox-holder">
		<?php do_meta_boxes('imageslider_new','normal', null); ?>
	</div>

</div>


<?php
function imageslider_meta_box(){
global $wpdb, $IMSL_DEFAULT_WIDTH, $IMSL_DEFAULT_HEIGHT;
?>

	<form name="frmImageSlider" action="<?php echo admin_url('admin.php') .'?page=imageslider-new'; ?>" method="post">
		<input type="hidden" name="admin-action" value="create" />

		<ul class="imsl-ul-settings">
			<li>
				<label>Title:</label>
				<input type="text" name="slide_show_title" value="">
			</li>
			<li>
				<label>Width:</label>
				<input type="text" name="slide_show_width" value="<?php
				echo $IMSL_DEFAULT_WIDTH ? $IMSL_DEFAULT_WIDTH : 640;
				?>">
			</li>
			<li>
				<label>Height:</label>
				<input type="text" name="slide_show_height" value="<?php
				echo $IMSL_DEFAULT_HEIGHT ? $IMSL_DEFAULT_HEIGHT : 320;
				?>">
			</li>
			<li>
				<label>Transition time:</label>
				<input type="text" name="slide_show_transition_time" value="400">
			</li>
			<li>
				<label>Easing:</label>
				<select id="slide_show_easing" name="slide_show_easing">
				</select>
				<script type="text/javascript">
				(function ($, window, document, undefined){
				$(document).ready(function($){
					var opt, sel = $("#slide_show_easing");
					$.each( $.easing, function(name, impl){
						// Skip linear/jswing and any non functioning implementation
						if ($.isFunction(impl) && !/jswing/.test(name)){
							opt = $("<option></option>").val(name).text(name);
							if (name == "swing") opt.attr("selected", true)
							sel.append( opt );
						}
					});
				});
				}(jQuery, window, document));
				</script>
			</li>
			<li>
				<label>Theme:</label>
				<select id="slide-show-theme" name="slide-show-theme">
					<option value="basic" selected="selected">Basic</option>
					<option value="light">Light</option>
				</select>
			</li>
		</ul>

		<input type="submit" class="button-primary" value="Add slide show" />

	</form>

<?php } ?>