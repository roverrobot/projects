var CodeMirrorPath = DOKU_BASE.concat("/lib/plugins/projects/editor/codemirror/codemirror-5.8");

require.config({
	packages: [{
		name: "codemirror",
		location: CodeMirrorPath,
		main: "lib/codemirror"
	}]
}); 

require(["codemirror", "codemirror/mode/meta"], function(CodeMirror) {
	jQuery(".PROJECTS_EDITOR_CODEMIRROR").each( function() {
		var text = jQuery(this);
		var mode = text.attr("mode");
		if (!mode) {
			var id = JSINFO.id;
			var dots = id.split(".");
			var ext = dots[dots.length-1];
            var meta = CodeMirror.findModeByExtension(ext);
            if (meta) mode = meta.mode;
		}
		var module = "";
        if (mode) module = "codemirror/mode/".concat(mode).concat("/").concat(mode);
        require([module], function() {
            editor = CodeMirror.fromTextArea(text[0], {
            	lineNumbers: true,
                readOnly: true,
                mode: mode
            }); 
            editor.toggleReadOnly = function() {
                editor.setOption("readOnly", !editor.getOption("readOnly"));
            }
            editor.document = function() {
                return editor.getValue();
            }
            editor.isDirty = function() {
                return !editor.isClean();
            }
            if (!document.hasOwnProperty("editors"))
            	document.editors = {};
            document.editors[text.attr("id")] = editor;
        });
	});
});
