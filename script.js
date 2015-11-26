jQuery(function(){
    jQuery( "#PROJECTS_TABS" ).tabs();
});

function inArray(value, array)
{
	for (v in array)
		if (array[v] == value) return true;
	return false;
}

function loadEditor(files)
{
	var head = document.getElementsByTagName('head')[0];
	var paths = files.split(":");
	for (var i in paths) {
		path = paths[i];
		if (path.substring(path.length-3).toLowerCase()==".js") {
			var script= document.createElement('script');
   			script.type= 'text/javascript';
   			script.src= path;
   			head.appendChild(script);
		} else if (path.substring(path.length-4).toLowerCase()==".css") {
			var link= document.createElement('link');
   			link.rel= 'stylesheet';
   			link.href= path;
   			head.appendChild(link);
   		}
	}
}

jQuery(function() {
	var editors = [];
	jQuery("textarea").each (function() {
		var editor = jQuery(this).attr("editor");
		if (editor && !inArray(editor, editors)) {
			var files = jQuery(this).attr("require");
			loadEditor(files);
		}
	});
	jQuery("form").each(function() {
		var id = jQuery(this).attr("editor");
		if (!id) return;
		jQuery("#".concat(id).concat('-cancel')).hide();
		jQuery(this).submit(function() {
			return editorSubmit(id);
		});
	});
});

function editorSubmit(id) {
	var match = "#".concat(id).concat("-edit");
	var button = jQuery(match);
	if (!button) return false;
	var div = jQuery("#".concat(id).concat("-cancel"));
	if (!div) return false;
	var submit = false;
	var editor = document.editors[id];
	editor.toggleReadOnly();
	editor.focus();
	if (button.html() == "edit") {
		button.html("save");
		div.show();
	}
	else {
		button.html("edit");
		div.hide();
		submit = editor.isDirty();
	}
	return submit;
}
