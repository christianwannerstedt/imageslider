;(function ($, window, document, undefined){

	$(document).ready(function($){

		// Add easing options
		var sel = $("#slide-show-easing"),
			sel_val = sel.val();
		$.each( $.easing, function(name, impl){
			if ($.isFunction(impl) && !/jswing/.test(name) && name != sel_val) sel.append( $("<option></option>").val(name).text(name) );
		});	

		// Slide Model
		var Slide = Backbone.Model.extend({
		    defaults: {
		    	position: 0,
		        id: "-",
		        title: "",
		        link: "",
		        thumb_url: ""
		    },
		    initialize: function(){},
		    sync: function(method, model, options){
		    	if (method == "update"){
					var	data = {
						action: "update_slide",
						slide_id: this.id,
						title: model.attributes.title,
						link: model.attributes.link
					};
					$.post(ajaxurl, data, function(response){
						if (response == 200){
							$("#slide-"+ data.slide_id +"-updated").fadeIn("slow", function(){
								$("#slide-"+ data.slide_id +"-updated").fadeOut("slow");
							});
						} else {
							alert("An error occured and the slide was not updated.. Please try again..");
						}
					});
				}

		    	// Delete slide
		    	else if (method == "delete"){
			    	if (confirm("Do you really want to delete this slide?")){
						$.post(ajaxurl, {action: "delete_slide", slide_id: this.id }, function(response) {
							if (response == 200){
								if (options.success) options.success(model);
							} else {
								alert("An error occured and the slide could not be deleted..");
							}
						});
					}
		    	}
		    }
		});

		// Slides collection
		var Slides = Backbone.Collection.extend({
		    model: Slide,
		    initialize: function (models, options) {
			}
		});

		// Edit list view
		var SlideShowView = Backbone.View.extend({
			initialize: function () {
				this.template       = _.template($('#slides-template').html());
	        	this.inner_template = _.template($('#slide-template') .html());

				this.collection.on("add", this.addSlide, this);
				this.collection.on("remove", this.removeSlide, this);
			},
			setupSorting: function(){
				var _this = this;
				$("#edit-slides-list").sortable({
					update: function(event, ui){
						_this.savePosition();
					}
				});
				return this;
			},
			events: {
				"click .delete-slide-button":  "deleteSlide",
				"click .update-slide-button": "updateSlide"
			},
			deleteSlide: function(e){
				var slide_id = $(e.currentTarget).attr("id").split("-")[2],
					slide = this.collection.get(slide_id);
				slide.destroy({
					wait: true,
					success: function(model, response){
					}
				});
			},
			updateSlide: function(e){
				var slide_id = $(e.currentTarget).attr("id").split("-")[2],
					slide = this.collection.get(slide_id);
				slide.save({
					title: $("#slide-"+ slide_id +"-title").val(),
					link: $("#slide-"+ slide_id +"-link").val()
				});
			},
			addSlide: function(slide){
				$('ul#edit-slides-list', this.el).append( this.inner_template(slide.toJSON()) );
			},
			removeSlide: function(slide){
				var _this = this;
				$("#slide-"+ slide.id, this.el).slideUp(function(){
					$("#slide-"+ slide.id, this.el).remove();
					_this.savePosition();
				});
			},
			savePosition: function(){
				var ids = [];
				$("#edit-slides-list > li").each(function(index){
					ids.push( $(this).attr("id").split("-")[1] );
				});
				$.post(ajaxurl, {action: "update_slide_position", ids: ids.join(",") }, function(response) {
					if (response != 200){
						alert("An error occured and the new position could not be saved..");
					}
				});
			},
			render: function(){
			    this.$el.html( this.template( {slides: this.collection.toJSON()} ) );
			    return this;
			}
		});


		// Create the backbone view
		var slide_show_view = new SlideShowView({ 
			el: $("#edit-slides-list-container"),
			collection: new Slides(slides_json)
		}).render().setupSorting();


		// Setup slide show form event
		$("#form-update-imsl-settings").submit(function(e){
			e.preventDefault();
			$("#imsl-update-settings-submit").attr("disabled", "disabled");
			$("#imsl-update-settings-saving").show();

			var data = {
				action: "update_slide_show_settings",
				slide_show_id: $("#slide-show-id").val(),
				title: $("#slide-show-title").val(),
				transition_time: $("#slide-show-transition-time").val(),
				easing: $("#slide-show-easing").val()
			};

			$.post(ajaxurl, data, function(response) {
				$("#imsl-update-settings-submit").removeAttr("disabled");
				$("#imsl-update-settings-saving").fadeOut();
				if (response != 200){
					alert("An error occured and the setting was not updated.. Please try again..");
				}
			});
		});


		// Setup Plupload
		var pluploader = new Pluploader($, {
			url: $('#plupload-upload-path').val(),
			flash_swf_url: $('#plupload-flash-url').val(),
			image_width: image_width,
			image_height: image_height,
			multipart_params: {
				slide_show_id: slide_show_id,
				min_width: image_width,
				min_height: image_height,
				thumb_width: 150,
				thumb_height: 100				
			}
		}).init();

		$(pluploader).on("PluploadFileUploaded", function(event, json_response){
			slide_show_view.collection.add({
				id: json_response.id,
				thumb_url: json_response.thumb_url
			});
		});
	});
	
}(jQuery, window, document));