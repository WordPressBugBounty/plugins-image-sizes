jQuery(function ($) {
	var limit = $("#image-sizes_regenerate-thumbs-limit").val();

	$("#image-sizes_regenerate-thumbs-limit").bind("keyup mouseup", function () {
		limit = $(this).val();
	});

	var offset = 0;
	var thumbs_deleted = 0;
	var thumbs_created = 0;

	function regenerate( limit, offset, thumbs_deleted, thumbs_created ) {
		$.ajax({
			url: THUMBPRESS.ajaxurl,
			type: "POST",
			data: {
				action: "image_sizes-regen-thumbs",
				offset: offset,
				limit: limit,
				thumbs_deleteds: thumbs_deleted,
				thumbs_createds: thumbs_created,
				_nonce: THUMBPRESS.nonce,
			},
			success: function (res) {
                if( res.status == 1 ) {
                    if (res.progress != 100) {
                        regenerate(limit, res.offset, res.thumbs_deleted, res.thumbs_created);
                    }else{
                        $("#image_sizes-regen-thumbs").text(THUMBPRESS.regen).attr("disabled", false);
                    }
					if( res.progress ) {
						var progress = Math.round(res.progress);
					   $(".thumbpress-progressbar").attr( 'style', "--value:" + progress );
					   $(".thumbpress-progressbar").attr('data-content', progress);
				   }
				   	$(".thumbpress-processs-message").html(res.message);
                    $("#processed-count").html(res.offset);
					$("#deleted-count").html(res.thumbs_deleted);
					$("#created-count").html(res.thumbs_created);
					$(".thumbpress-progressbar").show();
					$(".thumbpress-action-no-process").hide();
					$(".thumbpress-processs-message").show();
                }
                if( res.status == 2 ) {
					$("#thumbpress-action-result").hide();
					$('.thumbpress-action-no-result').show();
					$("#image_sizes-regen-thumbs").text(THUMBPRESS.regen).attr("disabled", false);
                }
			},
			error: function (err) {
				$("#image_sizes-regen-thumbs").text(THUMBPRESS.regen).attr("disabled", false);
			},
		});
	}

	// cx-regen-thumbs
	$("#image_sizes-regen-thumbs").click(function (e) {
		$("#image_sizes-regen-thumbs").text(THUMBPRESS.regening).attr("disabled", true);
		$(".thumbpress-action-no-result, thumbpress-action-failed, .thumbpress-action-no-process, #thumbpress-pro-view, .thumbpress-processs-message").hide();
		$(".thumbpress-progressbar").attr( 'style', "--value:0");
		$(".thumbpress-progressbar").attr('data-content', 0);
		$("#processed-count").html(0);
		$("#deleted-count").html(0);
		$("#created-count").html(0);
		$("#thumbpress-action-result").show();
		regenerate(limit, offset, thumbs_deleted, thumbs_created);
	});

	// Schedule regen thumbnail
	$(document).on("click", "#image_sizes-schedule-regen-thumbs", function(e) {
		thumbpress_modal(true);

		var limit = $('#image-sizes_regenerate-thumbs-limit').val();
		$.ajax({
			url: THUMBPRESS.ajaxurl,
			data: {
				'action' 	: 'thumbpress_schedule_regenerate-thumbs',
				'_wpnonce'	: THUMBPRESS.nonce,
				'limit'		: limit,
			},
			type: 'POST',
			dataType: 'json',
			success: function(response) {
				thumbpress_modal(false);
				if( response.status == 2 ) {
					$("#thumbpress-action-result, .thumbpress-action-failed, .thumbpress-action-no-process, #thumbpress-pro-view, .thumbpress-processs-message").hide();
					$('.thumbpress-action-no-result').show();
					$("#image_sizes-schedule-regen-thumbs").text(THUMBPRESS.regen).attr("disabled", false);
				}
				else{
					location.reload();
				}
			},
			error: function (err) {
				thumbpress_modal(false);
				console.log(err);
			},
		});
	});
});
  