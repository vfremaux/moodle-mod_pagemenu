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
 * Index page, shows all instance
 * and alphabetic order and displays
 * some instance settings if you have
 * proper capability
 *
 * @author Mark Nielsen
 * @version $Id: index.php,v 1.1 2010/03/03 15:30:09 vf Exp $
 * @package pagemenu
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

$id = required_param('id', PARAM_INT);   // Course ID.

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course->id);

add_to_log($course->id, 'pagemenu', 'view all', "index.php?id=$course->id", '');

// Get all required strings.
$strpagemenus = get_string('modulenameplural', 'pagemenu');
$strpagemenu  = get_string('modulename', 'pagemenu');

$url = $CFG->wwwroot.'/mod/pagemenu/index.php';
$PAGE->set_url($url);
$PAGE->set_title($strpagemenus);
$PAGE->set_heading($strpagemenus);
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
$PAGE->set_button('');
$PAGE->set_headingmenu(navmenu($course));

echo $OUTPUT->header();

if (!$pagemenus = get_all_instances_in_course('pagemenu', $course)) {
    notice("There are no pagemenus", "$CFG->wwwroot/course/view.php?id=$course->id");
    die;
}

// Sort the instances by name.
$function = create_function('$a, $b', 'return strnatcasecmp($a->name, $b->name);');
usort($pagemenus, $function);

$table              = new stdClass;
$table->head        = array($strpagemenus);
$table->width       = '60%';
$table->tablealign  = 'center';
$table->cellpadding = '5px';
$table->cellspacing = '0';
$table->data        = array();

$addheaders = false;
foreach ($pagemenus as $pagemenu) {
    $name    = format_string($pagemenu->name);
    $url     = "$CFG->wwwroot/mod/pagemenu/view.php?id=$pagemenu->coursemodule";
    $class   = '';
    $context = context_module::instance($pagemenu->coursemodule);

    if (!$pagemenu->visible) {
        // Show dimmed if the mod is hidden
        $class = ' class="dimmed"';
    }

    $link = '<a title="'.s($name)."\"$class href=\"$url\">$name</a>";

    if (has_capability('mod/pagemenu:manage', $context)) {
        $addheaders = true;

        $displayname = $useastab = get_string('no');
        if ($pagemenu->displayname) {
            $displayname = get_string('yes');
        }
        if ($pagemenu->useastab) {
            $useastab = get_string('yes');
        }

        $table->data[] = array($link, $displayname, $useastab, $pagemenu->taborder);
    } else if (has_capability('mod/pagemenu:view', $context)) {
        $table->data[] = array($link);
    }
}

if ($addheaders) {
    // Has mod/pagemenu:manage in at least one of the instances, so show 3 columns instead of 1.
    $table->head = array_merge($table->head, array(get_string('displayname', 'pagemenu'), get_string('useastab', 'pagemenu'), get_string('taborder', 'pagemenu')));

    // Add padding to those rows that the user does not have mod/pagemenu:manage cap.
    $cols = count($table->head);
    foreach ($table->data as $key => $row) {
        if (count($row) < $cols) {
            $table->data[$key] = array_pad($row, $cols, '');
        }
    }
}

if (!empty($table->data)) {
    echo html_writer::table($table);
    echo $OUTPUT->footer($course);
} else {
    notice("There are no pagemenus", "$CFG->wwwroot/course/view.php?id=$course->id");
}
