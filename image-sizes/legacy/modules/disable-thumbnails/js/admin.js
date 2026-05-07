jQuery(function ($) {
	// drag and drop disable thumbnail sizes
	if($('body').hasClass('toplevel_page_thumbpress')) {
		init_draggable($(".draggable-item"));

		$("#sortable2").sortable({
			connectWith: "#sortable1, #sortable2",
			items: ".draggable-item, .sortable-item",
			start: function (event, ui) {
				$("#sortable1").sortable("enable");
				$("ul.image_sizes-sortable.disable li input").attr("name", "disables[]");
	
				var _length = $("ul.image_sizes-sortable.disable li").length - 1;
				$(".image_sizes-default-thumbnails-panel h4 .disables-count").text(
					_length
					);
	
				var _length = $("ul.image_sizes-sortable.enable li").length;
				$(".image_sizes-default-thumbnails-panel h4 .enables-count").text(
					_length
					);
			},
			receive: function (event, ui) {
				if (ui.item.hasClass("ui-draggable")) {
					ui.item.draggable("destroy");
				}
			},
		});

		$("#sortable1").sortable({
			connectWith: "#sortable1, #sortable2",
			items: ".draggable-item, .sortable-item",
			receive: function (event, ui) {
				$("#sortable1").sortable("disable");
				var widget = ui.item;
				init_draggable(widget);
				$("ul.image_sizes-sortable.enable li input").attr("name", "");
	
				var _length = $("ul.image_sizes-sortable.disable li").length;
				$(".image_sizes-default-thumbnails-panel h4 .disables-count").text(_length);
	
				var _length = $("ul.image_sizes-sortable.enable li").length;
				$(".image_sizes-default-thumbnails-panel h4 .enables-count").text(_length);
			},
		});
	}

	function init_draggable(widget) {
		widget.draggable({
			connectToSortable: "#sortable2",
			stack: ".draggable-item",
			revert: true,
			revertDuration: 200,
			start: function (event, ui) {
				$("#sortable1").sortable("disable");
			},
		});
	}
});
  