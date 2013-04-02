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

require_login($course->id);
require_capability('mod/pagemenu:view', get_context_instance(CONTEXT_MODULE, $cm->id));

pagemenu_print_header($cm, $course, $pagemenu);
print_box(pagemenu_build_menu($pagemenu->id), 'boxwidthnormal boxaligncenter');
print_footer($course);

?>