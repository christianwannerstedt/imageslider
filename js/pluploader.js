var Pluploader = function($, args){
	var container_elem 		= args.container || $("#plupload-container"),
		browse_button_elem 	= args.browse_button || $("#plupload-browse-button"),
		list_elem 			= args.list || $("#plupload-list"),
		submit_button_elem	= args.submit_button || $("#plupload-submit-button")
		_this = this;
		
	var uploader = new plupload.Uploader({
		runtimes: 'html5,flash,html4',
	    browse_button: browse_button_elem.attr("id"),
	    container: container_elem.attr("id"),
	    drop_element: container_elem.attr("id"),
		max_file_size: '1000mb',
		/*resize: {
			width: args.image_width || 320, 
			height: args.image_height || 240,
			quality: args.image_quality || 90
		},*/
		url: args.url || 'upload.php',
		flash_swf_url: args.flash_swf_url || "/plupload/js/plupload.flash.swf",
		filters: [
			{ title: "Image files", extensions: "jpg,gif,png" }
		],
		multipart_params: args.multipart_params
	});

	// Bind events
	uploader.bind('Init', function(up, params) {
		if (params.runtime == "html5"){
			container_elem.addClass("drag-drop");
		}
	});
	uploader.bind('FilesAdded', function(up, files) {
		$.each(files, function(i, file) {
			list_elem.append(

				$("<li></li>").attr("id", file.id)
					.append(
						$("<div></div>").addClass("plupload-file-head")
							.append( $("<span></span>").addClass("status").text("Ready") )
							.append( $("<strong></strong>").text( file.name +" ("+ plupload.formatSize(file.size) +")") )
							.append( $("<span></span>").addClass("error") )
					)
					.append( 
						$("<div></div>").addClass("progress").addClass("progress-striped").addClass("active").append(
							$("<div></div>").addClass("bar").css("width", "0%")
						) 
					)

			);
		});
		
		list_elem.show();
		submit_button_elem.show();
	});
	uploader.bind('UploadProgress', function(up, file){
		if (file.percent == 100) $("#" + file.id + " .status").text("Completed");
		else $("#" + file.id + " .status").text("Uploading ("+ file.percent +"%)");
		$("#" + file.id + " .progress .bar").css("width", file.percent + "%")
	});
	uploader.bind('Error', function(up, err){
		list_elem.append("<li>Error: " + err.code +", Message: " + err.message + (err.file ? ", File: " + err.file.name : "") +"</li>");
	});
	uploader.bind('FileUploaded', function(up, file, response) {
		var json_response = JSON.parse(response.response);
		if (json_response.error){
			console.log("FileUploaded Error...");	
			list_elem.append("<li>Error: " + json_response.error.code +", Message: " + json_response.error.message + (file ? ", File: " + file.name : "") + "</li>");
		}  else {
			$(_this).trigger('PluploadFileUploaded', [json_response]);
			$('#' + file.id + " .progress").remove();
		}
	});
	uploader.bind("UploadComplete", function(){
		submit_button_elem.removeAttr('disabled');
	});
	submit_button_elem.click(function(){
		submit_button_elem.attr('disabled', true);
		uploader.start();
	});

	// Public functions
	this.init = function(){
		uploader.init();
		return this;
	}

	return this;
}