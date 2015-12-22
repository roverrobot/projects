/* DOKUWIKI:include_once editor/require.js */

function submitForm(data, form) {
	var text = "";
	if (data.editing) {
		text = data.getValue();
		data.editing = data.oldValue != text;
	}
	if (!data.editing) {
		data.updateDisplay();
		return false;
	}
	var tag = jQuery("<textarea/>").addClass("hidden").attr("name", "new").text(text);
	form.append(tag);
	tag = jQuery("<textarea/>").addClass("hidden").attr("name", "old").text(data.oldValue);
	form.append(tag);
	return true;
}

function loadFiles(files)
{
	for (var i in files) {
		path = files[i];
		if (path.substring(path.length-3).toLowerCase()==".js") {
			jQuery.getScript(path);
		} else if (path.substring(path.length-4).toLowerCase()==".css") {
			var css = jQuery("<link>");
			css.attr({rel:  "stylesheet", type: "text/css", href: path});
   			jQuery("head").append(css);
   		}
	}
}

function onlyUnique(value, index, self) { 
    return value && self.indexOf(value) === index;
}

// load tabs
jQuery(function() {
    jQuery( ".PROJECTS_TABS" ).tabs({
        activate: function(event, ui) {
        	ui.newPanel.find("textarea[editor]").each(function() {
        		jQuery(this).data("editor").refresh();
        	});
        }
    });
});

// set action links
jQuery(function() {
	jQuery(".action_link").click(function() {
		jQuery(this).parent().parent().submit();
		return false;
	});
});

// load editors
jQuery(function() {
	var files = jQuery("textarea[editor]").map(function() {
		return jQuery(this).attr("require");
	}).get().join(":").split(":").filter(onlyUnique);
	loadFiles(files);
});

// setup editor control logic
jQuery(function() {
	jQuery(document).on("EditorReady", function(e) {
		var edit_form = jQuery(".editor_edit_form");
		if (edit_form.length == 0) return;
		if (edit_form.attr("editor") != e.editor.editor_id) return;
		var save_form = jQuery(".editor_save_form");
		if (save_form.length == 0) return;
		if (save_form.attr("editor") != e.editor.editor_id) return;
		var save_controls = save_form.parent();
		var data = {
			editor: e.editor,
			edit_form: edit_form,
			save_form: save_form,
			save_controls: save_controls,
			oldValue: e.editor.document(),
			editing: false
		};
		data.getValue = function() {
			return this.editor.document();
		}
		data.updateDisplay = function() {
			if (!this.editing) {
				this.edit_form.show();
				this.save_controls.hide();
				this.editor.setReadOnly(true);
			} else {
				this.edit_form.hide();
				this.save_controls.show();
				this.editor.setReadOnly(false);
				this.editor.focus();
			}
		}
		data.updateDisplay();
		edit_form.submit(data, function(e) {
			data.editing = true;
			data.updateDisplay();
			return false;
		})
		save_form.submit(data, function(e) { 
			return submitForm(e.data, jQuery(this)); 
		});
	});
});

// conflict resolving
jQuery(function() {
	var form = jQuery("diff_form");
	if (form.length == 0) return;
	// select conflicting branches
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
	// accept nonconflicting changes
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
	// submit changes
	var data = { editing: true }
	data.codeList = function(list) {
		return list.map(function() { return jQuery(this).html(); })
			.get().join("\n");
	}
	data.oldValue = data.codeList(jQuery(".diffold, .diffcopy").children("pre"));
	data.getValue = function() {
		return this.codeList(jQuery("pre[diffpick=1]"));
	}
	data.updateDisplay= function() { data.editing = true; }
	form.submit(data, function(e) {		
		if (jQuery('pre[diffpick="-1"]').length > 0) {
			alert('There are still unresolved conflicts!');
			return false;
		}
		return submitForm(e.data, jQuery(this));
	});
});

// dependency handling
jQuery(function() {
	jQuery("#dependency_update_controls").each(function() {
		var control = jQuery(this);
		var data = {
			control: control,
			newUse: jQuery("#new_dependency_name"),
			editing: false
		}
		data.getList = function() {
			return jQuery("span[use]").map(function() { return jQuery(this).attr("use"); })
				.get().filter(onlyUnique);
		}
		data.getValue = function() { return this.getList().join("\n"); }
		data.oldValue = data.getValue();
		data.updateDisplay = function() {
			if (this.editing) 
				this.control.show();
			else this.control.hide();
		}
		data.updateDisplay();
		control.children("#dependency_update_form").submit(data, function(e) {
			submitForm(e.data, jQuery(this));
		});
		jQuery("#add_dependency").click(data, function (e) {
			var use = data.newUse.val();
			data.newUse.val('');
			if (use) {
				e.data.editing = true;
				e.data.updateDisplay();
				add_dependency(e.data, use); 
			}
			return false;
		});
		jQuery(".remove_dependency").click(data, remove_dependency);
	});
});

// maker change handling
jQuery(function() {
	var data = {
		controls: jQuery("#maker_controls"),
		select: jQuery("#PROJECTS_maker"),
		editing: false
	}
	data.getValue = function() {
		return this.select.val();
	}
	data.oldValue = data.getValue();
	data.updateDisplay = function() {
		if (!this.editing)
			this.controls.hide();
		else this.controls.show();
	}
	data.updateDisplay();
	data.select.change(data, function(e) {
		e.data.editing = true; 
		e.data.updateDisplay();
	});
	jQuery("#maker_select_form").submit(data, function(e) {
		return submitForm(e.data, jQuery(this));
	});
});

function remove_dependency(e) {
	e.data.editing = true;
	e.data.updateDisplay();
	jQuery(this).parent().remove();; 
	return false;
}

function add_dependency(data, use) {
	if (data.getList().indexOf(use) >= 0) return;
	var li = jQuery("<li>");
	var span = jQuery("<span>");
	span.addClass("dependency");
	span.attr("use", use);
	span.text(use);
	li.append(span);li.append("(");
	var link = jQuery("<a>");
	link.addClass("remove_dependency");
	link.addClass("action");
	link.attr("href", "");
	link.text("remove");
	link.click(data, remove_dependency);
	li.append(link);li.append(")");
	jQuery(".dependency_list").append(li);
}
 