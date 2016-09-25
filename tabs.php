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
 * Tabs
 *
 * @author Mark Nielsen
 * @package mod_pagemenu
 */

defined('MOODLE_INTERNAL') || die();

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

