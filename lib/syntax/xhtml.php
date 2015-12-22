<?php

function cancel_button() {
    $form = new Doku_Form(array('class' => 'action_cancel'));
    $form->addElement(form_makeButton('submit', 'show', 'cancel'));
    return $form->getForm();
}

function xhtml_action($action, $title, $hidden_fields = array(), $fields=array()) {
    $hidden_fields['do'] = $action;
    $name = preg_replace('/[^0-9A-Za-z_]+/', '_', $title);
    $formID = "PROJECTS_$name_form";
    $form = new Doku_Form(array('id' => $formID));
    foreach ($hidden_fields as $name => $value)
        $form->addHidden($name, $value);
    foreach ($fields as $field)
        $form->addElement($field);
    $form->addElement("<a href=\"\" class=\"action_link\">$title</a>");
    return '<span class="action">'.$form->getForm().'</span>';
}

function download_button($id) {
    $link = ml($id);
    return '<span class="action"><a href="javascript: window.location.href=' .
    "'$link'" . '">download</a> </span>';
}

function manage_files_button($id) {
    $link = wl($id, array('do' => 'projects.manage_files'));
    return "<span class=\"action\"><a href=\"$link\">manage files</a></span>";
}

function delete_button($id) {
    return xhtml_action('save', 'delete', array('id' => $id, 'wikitext' => ''));
}

function make_button($id, $remake=FALSE) {
    $title = ($remake) ? 'remake' : 'make';
    return xhtml_action('projects.make', $title, array('id' => $id, 'remake' => $remake));
}

function kill_button($id) {
    return xhtml_action('projects.kill', 'cancel generation', array('id' => $id));
}

function create_button($type) {
    return xhtml_action('projects.create', 'add',
        array('type' => $type), array(form_makeTextField('New')));
}
