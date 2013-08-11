jQuery(document).ready(function($){
	var awYoutubeList = $(".awYoutubeList");
	awYoutubeList.hide(0);
	var id = $("#awSelecter option:selected").first().attr("id");
	$("#list"+id).show(0);
	$("#awSelecter").change(function(){
		var id = $(this).children("option:selected").first().attr("id");
		awYoutubeList.hide(0);
		$("#list"+id).show(0);
	});
});
