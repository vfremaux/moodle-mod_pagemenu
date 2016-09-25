<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Menu editing
 *
 * @author Mark Nielsen
 * @version $Id: edit.php,v 1.1 2010/03/03 15:30:07 vf Exp $
 * @package pagemenu
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');
require_once($CFG->dirroot.'/mod/pagemenu/edit_form.php');

$id         = optional_param('id', 0, PARAM_INT); // Course Module ID.
$a          = optional_param('a', 0, PARAM_INT);  // Instance ID.
$linkid     = optional_param('linkid', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_ALPHA);
$linkaction = optional_param('linkaction', '', PARAM_ALPHA);

list($cm, $course, $pagemenu) = pagemenu_get_basics($id, $a);

require_login($course->id, true, $cm);
require_capability('mod/pagemenu:manage', context_module::instance($cm->id));

$formdata = array('id' => $cm->id, 'a' => $pagemenu->id);
$link     = null;

if (!empty($action)) {
    if (pagemenu_handle_edit_action($pagemenu, $action)) {
        redirect("$CFG->wwwroot/mod/pagemenu/edit.php?id=$cm->id");
    }
    if ($action == 'edit' and $linkid) {
        // We are editing a link.
        if (!$linktype = $DB->get_field('pagemenu_links', 'type', array('id' => $linkid))) {
            print_error('errorlinkid', 'pagemenu');
        }

        $link = mod_pagemenu_link::factory($linktype, $linkid);
        $formdata = array_merge($formdata, (array) $link->config);
    }
}

/*
 * Create the editing form which has dual purpose - add new
 * links of any type or edit a single link of any type
 */
$mform = new mod_pagemenu_edit_form(null, $link);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/pagemenu/edit.php', array('id' => $cm->id)));

} else if ($data = $mform->get_data()) {
    // Save form data.
    foreach (pagemenu_get_link_classes() as $link) {
        $link->save($data);
    }
    pagemenu_set_message(get_string('menuupdated', 'pagemenu'), 'notifysuccess');
    redirect(new moodle_url('/mod/pagemenu/edit.php', array('id' => $cm->id)));

} else if (!empty($linkaction)) {
    /*
     * These are special link actions that can be invoked by
     * a link class.  EG: hide show page menu items
     */
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }
    if (!in_array($linkaction, pagemenu_get_links())) {
        print_error('errorlinktype', 'pagemenu');
    }
    $link = mod_pagemenu_link::factory($linkaction);
    $link->handle_action();

    $editurl = new moodle_url('/mod/pagemenu/edit.php', array('id' => $cm->id));
    redirect($editurl);
}

$renderer = $PAGE->get_renderer('pagemenu');
$renderer->header($cm, $course, $pagemenu, 'edit', $mform->focus());

// Don't display menu when editing a single link.
if (!($action == 'edit' and $linkid)) {
    echo $renderer->build_menu($pagemenu->id, true);
}

// Print the form - remember it has duel purposes.
echo $OUTPUT->box_start('boxwidthwide boxaligncenter');
$mform->set_data($formdata);
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer($course);