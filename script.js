/* DOKUWIKI:include_once editor/require.js */

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
    jQuery( ".PROJECTS_TABS" ).tabs();

	var editors = [];
	jQuery("textarea[editor]").each (function() {
		var editor = jQuery(this).attr("editor");
		if (editor && !inArray(editor, editors)) {
			var files = jQuery(this).attr("require");
			loadEditor(files);
		}
	});
	jQuery("#editor_submit_form").each(function() {
		var form = jQuery(this);
		form.submit(function() { return editorSubmit(form); });
		form.parent().children("#action_cancel").hide();
	});
	jQuery("input[name^=diffaccept_").change(function() {
		var val = jQuery(this).val();
		var pick = jQuery(this).parent().parent().parent();
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
		var content = jQuery(this).children().children('input[name=new]');
		content.val(closing);
	});
	var deps_update = jQuery("#dependency_update_controls");
	deps_update.hide();
	jQuery("#add_dependency").click(function () { add_dependency(deps_update); return false; });
	jQuery(".remove_dependency").click(function() {
		enable_dependency_update(deps_update);
		jQuery(this).parent().remove();; 
		return false;
	});
	jQuery("#maker_controls").each(function() {
		var controls = jQuery(this);
		controls.hide();
		controls.parent().children("#PROJECTS_maker").change(function() {
			controls.show();
		});
	});
});

function add_dependency(deps_update) {
	enable_dependency_update(deps_update);
	var dep = jQuery("#new_dependency_name");
	var use = dep.val();
	dep.val('');
	if (use) {
		var code = '<li><span class="dependency" use="'.concat(use).concat('">')
					.concat(use).concat('</span>(<a href="" use="').concat(use)
					.concat('" class="remove_dependency action">remove</a>)</li>');
		var list = jQuery("span[use]");
		if (list.length == 0) {
			jQuery(".dependency_list").append(code);
		} else {
			var added = false;
			var dup = false;
			list.each(function() {
				var id = jQuery(this).attr("use");
				if (id == use) {
					dup = true;
					return false;
				}
				if (id && use < id) {
					var li = jQuery(this).parent();
					li.before(code);
					added = true;
					return false;
				}
			});
			if (dup) return;
			if (!added) list.last().parent().after(code);
		}
		jQuery(".remove_dependency").click(function () {
			enable_dependency_update(deps_update);
			jQuery(this).parent().remove();; 
			return false;
		});
	}
}

function editorSubmit(form) {
	var id = form.attr("editor");
	button = form.children().children("#editor_submit_button");
	if (button.length == 0) return false;
	submit = button.html() == "save";
	if (submit) button.html('edit'); else button.html('save');
	var cancel = form.parent().children("#action_cancel");
	if (submit) cancel.hide(); else cancel.show();
	var editor = document.editors[id];
	var text = editor.document();
	editor.toggleReadOnly();
	if (submit) {
		var tag = jQuery("<textarea/>").addClass("hidden").attr("name", "new").text(text);
		form.append(tag);
		submit = editor.isDirty();
	} else {
		editor.focus();
		var old = form.children("textarea[name=old]");
		if (old.length == 0) {
			var tag = jQuery("<textarea/>").addClass("hidden").attr("name", "old").text(text);
			form.append(tag);
		}
	}
	return submit;
}

function get_dependencies() {
	var deps = "";
	jQuery("span[use]").each(function(){
		var dep = jQuery(this).attr("use");
		deps = (deps) ? deps.concat("\n").concat(dep) : dep;
	});
	return deps;
}

function enable_dependency_update(deps_update) {
	if (deps_update.is(":visible")) return;
	deps_update.show();
	var deps = get_dependencies();
	var form = deps_update.children("#dependency_update_form");
	form.children().children("input[name=old]").val(deps);
	deps_update.submit(function() {
		var deps = get_dependencies();
		form.children().children("input[name=new]").val(deps);
	});
}
 