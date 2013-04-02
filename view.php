<?php
/**
 * Landing page for this module
 *
 * @author Mark Nielsen
 * @version $Id: view.php,v 1.2 2012-06-18 16:08:03 vf Exp $
 * @package pagemenu
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$a  = optional_param('a', 0, PARAM_INT); // Instance ID

list($cm, $course, $pagemenu) = pagemenu_get_basics($id, $a);

require_login($course->id, true, $cm);
require_capability('mod/pagemenu:view', context_module::instance($cm->id));

pagemenu_print_header($cm, $course, $pagemenu);
echo $OUTPUT->box(pagemenu_build_menu($pagemenu->id), 'boxwidthnormal boxaligncenter');
echo '<center>';
echo $OUTPUT->single_button(new moodle_url('/course/view.php?id='.$course->id), get_string('backtocourse', 'pagemenu'));
echo '</center>';

echo $OUTPUT->footer($course);

