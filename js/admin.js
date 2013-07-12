(function ($) {
	"use strict";
	$(function () {
		/*
		  Opens Wordpress Media Uploader
		  Adds click event listener to the upload button
		  Sends URL to media-upload.php
		  referer: upload-sermon
		  type: audio
		  TB_iframe: true //always true
		  post_id: 0 //does not assign media to a post
		*/
		jQuery('#sermon_upload_button').click(function() {
			// formfield = jQuery('#sermon_upload').attr('name');
			tb_show('Upload a Sermon', 'media-upload.php?referer=sermon-upload&type=audio&TB_iframe=true&post_id=0');
			return false;
		});

		/*
		  Overrides send_to_editor function
		  Outputs uploaded media url to an element

		  ONLY needed if uploading a single post
		*/
		window.send_to_editor = function(html) {
			parent.location.reload(1);
		}

		/*
		  TODO NOT WORKING 
		  Refreshes the page when the window is closed 
		  Event when another event fires?
		  Event when dom looses modal?
		*/
		jQuery("#TB_closeWindowButton").click(function() {
		    parent.location.reload(1);
		});

		/*
			Show details of sermon
		*/
      	jQuery("button").click(function () {
            var dl_id = "dl-" + this.id;
            jQuery("#" + dl_id).toggle('slow');
      	});

      	jQuery(".modal-footer .btn-primary").click(function (error) {
      		error.preventDefault();
      		jQuery('.form-horizontal').submit();
      	});
	});
}(jQuery));


function show_modal() {
	var modal = "";



	return modal;
}
//sdg