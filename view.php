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
 * Landing page for this module
 *
 * @author Mark Nielsen
 * @version $Id: view.php,v 1.2 2012-06-18 16:08:03 vf Exp $
 * @package pagemenu
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$a  = optional_param('a', 0, PARAM_INT); // Instance ID.

list($cm, $course, $pagemenu) = pagemenu_get_basics($id, $a);

require_login($course->id, true, $cm);
require_capability('mod/pagemenu:view', context_module::instance($cm->id));

$renderer = $PAGE->get_renderer('pagemenu');

echo $renderer->header($cm, $course, $pagemenu);
echo $OUTPUT->box($renderer->build_menu($pagemenu->id, false, true), 'boxwidthnormal boxaligncenter');
echo '<center>';
echo $OUTPUT->single_button(new moodle_url('/course/view.php?id='.$course->id), get_string('backtocourse', 'pagemenu'));
echo '</center>';

echo $OUTPUT->footer($course);
