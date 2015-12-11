<?php

function cancel_button() {
    $form = new Doku_Form(array('class' => 'action_cancel'));
    $form->addElement(form_makeButton('submit', 'show', 'cancel'));
    return $form->getForm();
}

function xhtml_action($name, $action, $title,
    $hidden_fields = array(), $inputs = array(), $script_condition='') {
    $hidden_fields['do'] = $action;
    $name = preg_replace('/[^0-9A-Za-z_]+/', '_', $name);
    $formID = "form$action$name";
    $form = new Doku_Form($formID, 'doku.php');
    foreach ($hidden_fields as $name => $value)
        $form->addHidden($name, $value);
    foreach ($inputs as $input)
        $form->addElement($input);
    $script = "form=jQuery('#$formID')[0];form.submit()";
    if ($script_condition) $script = 'if('.$script_condition.'){'.$script.';}';
    $form->addElement("<a href=\"javascript: $script\">$title</a>");
    return '<span class="action">'.$form->getForm().'</span>';
}

function download_button($id) {
    $link = ml($id);
    return '<span class="action"><a href="javascript: window.location.href=' .
    "'$link'" . '">download</a> </span>';
}

function manage_files_button($id) {
    $link = wl($id, array('do' => 'manage_files'));
    return '<span class="action"><a href="javascript: window.location.href=' .
    "'$link'" . '">manage files</a> </span>';
}

function delete_button($id) {
    return xhtml_action($id, 'save', 'delete',
        array('id' => $id, 'wikitext' => ''), array(),
        'confirm('."'".'File '.noNS($id).' will be deleted. ' .
        'This file can be recovered from old revisions in the future.'."'".')');
}

function make_button($id, $remake=FALSE) {
    $title = ($remake) ? 'remake' : 'make';
    return xhtml_action($id, 'make', $title, array('id' => $id, 'remake' => $remake));
}

function kill_button($id) {
    return xhtml_action('cancel', 'kill', 'cancel generation', array('id' => $id));
}

function create_button($id, $type) {
    return xhtml_action($type, 'create', 'add',
        array('type' => $type, 'id' => $id), array(form_makeTextField('New')));
}
