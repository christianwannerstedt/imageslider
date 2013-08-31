<?php
global $screen_layout_columns;
add_meta_box("imageslider_content", "Slide shows", "imageslider_meta_box", "imageslider", "normal", "core");


// ****************************** Structure ******************************
?>

<div class="wrap">
	<div id="imageslider-index-container" class="metabox-holder">
		<div id="post-body" class="has-sidebar">
			<div id="post-body-content">
				<?php do_meta_boxes('imageslider','normal', null); ?>
			</div>
		</div>
		<br class="clear"/>
	</div>
</div>


<?php
// ****************************** Slide show list ******************************
function imageslider_meta_box(){
global $slide_shows;
?>

	<ul id="slideshow-content">
		<?php if (sizeof($slide_shows) == 0){ ?>
			There are no slide shows.
		<?php } else { ?>
			<?php foreach ($slide_shows as $slide_show) { ?>
				<li class="slideshow" id="slideshow-<?php echo $slide_show->id; ?>">

					<div class="clearfix">
						<ul>
							<li><strong>Title:</strong> <?php echo $slide_show->title; ?></li>
							<li><strong>Slides:</strong> <?php
								$slide_count = sizeof($slide_show->slides);
								echo ($slide_count == 1) ? "1 slide" : $slide_count ." slides";
							?></li>
							<li><strong>Size: </strong> <?php echo $slide_show->width ." x ". $slide_show->height; ?> px</li>
							<li><span>You can add this slide show to any page or post, by writing <strong>[SLIDE_SHOW_<?php echo $slide_show->id; ?>]</strong></span></li>
						</ul>

						<div>
							<?php foreach ($slide_show->slides as $slide) { ?>
								<img src="<?php echo $slide->thumb_url; ?>">
							<?php } ?>
							<br>
						</div>
					</div>

					<div class="clearfix">
						<a href="<?php echo admin_url('admin.php') .'?page=imageslider-edit-slide-show&slide_show_id='. $slide_show->id; ?>" class="button-primary">Edit</a>

						<form name="frmImageSlider" action="<?php echo admin_url('admin.php') .'?page=imageslider-index'; ?>" method="post" onsubmit="return confirm('Do you really want to delete this slide show?!?');">
							<input type="hidden" name="admin-action" value="delete">
							<input type="hidden" name="sid" value="<?php echo $slide_show->id; ?>">
							<input type="submit" class="button-secondary" value="Delete">
						</form>
					</div>

				</li>
			<?php } ?>
		<?php } ?>
	</ul>

<?php } ?>