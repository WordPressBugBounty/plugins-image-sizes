jQuery(function ($) {
    function convert( limit, offset ) {
		$.ajax({
			url: THUMBPRESS.ajaxurl,
			type: "POST",
			data: {
				action: "thumbpress_convert_images",
				offset: offset,
				limit: limit,
				_wpnonce: THUMBPRESS.nonce,
			},
			success: function (res) {
                if( res.status == 1 ) {
                    if (res.progress != 100) {
                        convert(limit, res.offset);
                    }else{
                        $("#thumbpress-convert-now").text(THUMBPRESS.convertNow).attr("disabled", false);
                    }

                    if( res.progress ) {
					   var progress = Math.round(res.progress);
					   $(".thumbpress-progressbar").attr( 'style', "--value:" + progress );
					   $(".thumbpress-progressbar").attr('data-content', progress);
				   }
                    $(".thumbpress-processs-message").html(res.message);
                    $("#processed-count").html(res.offset);
					$("#converted-count").html(res.offset);
					$(".thumbpress-progressbar").show();
					$(".thumbpress-action-no-process").hide();
                    $(".thumbpress-processs-message").show();
                }
                if( res.status == 2 ) {
                    $("#thumbpress-action-result").hide();
					$('.thumbpress-action-no-result').show();
					$("#thumbpress-convert-now").text(THUMBPRESS.convertNow).attr("disabled", false);
				}
			},
			error: function (err) {
				$("#thumbpress-convert-now").text(THUMBPRESS.converting).attr("disabled", false);
			},
		});
	}

    $(document).ready(function () {
        $(document).on("click", "#thumbpress-convert-image", function () {
            thumbpress_modal(true);

            $.ajax({
                url: THUMBPRESS.ajaxurl,
                type: "POST",
                data: {
                    action: "thumbpress_convert_single_image",
                    _wpnonce: THUMBPRESS.nonce,
                    image_id: $(this).data("image_id"),
                },
                success: function (resp) {
                    if( resp.status == 2 ) {
                        $(".thumbpress-processs-message").hide();
                        $("#thumbpress-action-result").hide();
                        $('.thumbpress-action-no-result').show();
                        $("#thumbpress-convert-image").text(THUMBPRESS.convertNow).attr("disabled", false);
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

        $(document).on("click", "#thumbpress-convert-now", function () {
            var limit = $('#thumbpress-convert-limit').val();
            var offset = 0;
            $("#thumbpress-convert-now").text(THUMBPRESS.converting).attr("disabled", true);
            $(".thumbpress-action-no-result, thumbpress-action-failed, .thumbpress-action-no-process, #thumbpress-pro-view, .thumbpress-processs-message").hide();
            $(".thumbpress-progressbar").attr( 'style', "--value:0");
            $(".thumbpress-progressbar").attr('data-content', 0);
            $("#processed-count").html(0);
            $("#converted-count").html(0);
            $("#thumbpress-action-result").show();
            convert(limit, offset);
        });

        $(document).on("click", "#thumbpress-convert-background", function () {
            thumbpress_modal(true);
            var convert_val = $('#thumbpress-convert-limit').val();

            $.ajax({
                url: THUMBPRESS.ajaxurl,
                type: "POST",
                data: {
                    action: "thumbpress_schedule_image_conversion",
                    _wpnonce: THUMBPRESS.nonce,
                    'convert_val': convert_val,
                },
                success: function (resp) {
                    thumbpress_modal(false);
                    if( resp.status == 2 ) {
                        $("#thumbpress-action-result, .thumbpress-action-failed, .thumbpress-action-no-process, #thumbpress-pro-view, .thumbpress-processs-message").hide();
                        $('.thumbpress-action-no-result').show();
                        $("#thumbpress-convert-image").text(THUMBPRESS.convertNow).attr("disabled", false);
                    }
                    else {
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
});
