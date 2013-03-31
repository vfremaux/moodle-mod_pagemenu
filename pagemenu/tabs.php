<?php  // $Id: tabs.php,v 1.1 2010/03/03 15:30:09 vf Exp $
/**
* Tabs
*
* @author Mark Nielsen
* @version $Id: tabs.php,v 1.1 2010/03/03 15:30:09 vf Exp $
* @package pagemenu
*/

/// This file to be included so we can assume config.php has already been included.

if (!defined('MOODLE_INTERNAL')) die('You cannot call this script in that way');

if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('pagemenu', $pagemenu->id);
}
if (has_capability('mod/pagemenu:manage', context_module::instance($cm->id))) {
    $tabs = $row = $inactive = array();

    $row[] = new tabobject('view', "$CFG->wwwroot/mod/pagemenu/view.php?id=$cm->id", get_string('view', 'pagemenu'));
    $row[] = new tabobject('edit', "$CFG->wwwroot/mod/pagemenu/edit.php?id=$cm->id", get_string('edit', 'pagemenu'));

    $tabs[] = $row;

    print_tabs($tabs, $currenttab, $inactive);
}

?>
