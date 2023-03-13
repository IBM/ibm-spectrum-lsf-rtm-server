// $Id$
metashown = 0; // address synchronization problems with mousein/mouseout and opendialog/closedialog

function openDialog(event, meta_id){
	$.get(urlPath + "plugins/meta/metadata.php?action=popup&meta_id=" + meta_id,
	function(data) {
		if (metashown != 0 && data.length > 1) {
			$("#metadialog").html(data);
			$("#meta_tabs").tabs({
				event: "mouseover"
			});
			$("#metadialog").siblings("div.ui-dialog-titlebar").remove();
			if ($('#metadiaglog').dialog('instance')) {
				$("#metadialog").dialog("open");
			}
		}
	});
	$("#metadialog").dialog("option", "position", {
		my: "left+10% center",
		at: "center",
		of: event
	});

	return false;
};

function closeDialog() {
	if (metashown != 1) {
		if ($('#metadiaglog').dialog('instance')) {
			$("#metadialog").dialog("close");
		}
	}
}
