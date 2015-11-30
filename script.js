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
			return editorSubmit(jQuery(this));
		});
	});
	jQuery("input[name=diffconflict").change(function() {
		var val = jQuery(this).val();
		var pick = jQuery(this).parent().parent();
		var orig = pick.children(".diffold");
		var orig_code = orig.children("pre");
		var closing = pick.children(".diffnew");
		var closing_code = closing.children("pre");
		closing.insertAfter(orig);
		switch (val) {
			case "old":
				orig_code.attr("diffpick", 1);
				closing_code.attr("diffpick", 0);
				break;
			case "new":
				orig_code.attr("diffpick", 0);
				closing_code.attr("diffpick", 1);
				break;
			case "old/new":
				orig_code.attr("diffpick", 1);
				closing_code.attr("diffpick", 1);
				break;
			case "new/old":
				orig_code.attr("diffpick", 1);
				closing_code.attr("diffpick", 1);
				orig.insertAfter(closing);
				break;
		}
	});
	jQuery("input[name=diffaccept").change(function() {
		var val = jQuery(this).attr('checked');
		var pick = jQuery(this).parent().parent();
		var orig = pick.children(".diffold");
		var orig_code = orig.children("pre");
		var closing = pick.children(".diffnew");
		var closing_code = closing.children("pre");
		if (val) {
			orig_code.attr("diffpick", 0);
			closing_code.attr("diffpick", 1);
		} else {
			orig_code.attr("diffpick", 1);
			closing_code.attr("diffpick", 0);
		}
	});
	jQuery("#diff_form").submit(function(){
		if (jQuery('pre[diffpick="-1"]').length > 0) {
			alert('There are still unresolved conflicts!');
			return false;
		}
		var orig = "";
		jQuery(".diffold, .diffcopy").each(function() {
			var code = jQuery(this).children('pre').html();
			if (code.length > 0) 
				orig = orig.concat('\n').concat(code);
		});
		var old = jQuery(this).children().children('input[name=old]');
		old.val(orig);
		var closing = "";
		jQuery("pre[diffpick=1]").each(function() {
			var code = jQuery(this).html();
			if (code.length > 0) 
				closing = closing.concat('\n').concat(code);
		});
		var content = jQuery(this).children().children('input[name=content]');
		content.val(closing);
	});
});

function editorSubmit(form) {
	var id = form.attr("editor");
	var match = "#".concat(id).concat("-edit");
	var button = jQuery(match);
	if (!button) return false;
	var div = jQuery("#".concat(id).concat("-cancel"));
	if (!div) return false;
	var submit = false;
	var editor = document.editors[id];
	editor.toggleReadOnly();
	editor.focus();
	var old_match = "PROJECT_FILE_old_".concat(id);
	var old = jQuery("#".concat(old_match));
	if (button.html() == "edit") {
		button.html("save");
		div.show();
		var text = editor.document();
		if (old.length == 0) {
			var tag = '<textarea style="visibility: hidden;" id="'.concat(old_match).concat('" name="old">')
				.concat(text)
				.concat('</textarea>');
			form.append(tag);
		}
	}
	else {
		button.html("edit");
		div.hide();
		submit = editor.isDirty();
	}
	return submit;
}
