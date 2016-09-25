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
 * upgrade processes for this module.
 *
 * @package   mod_pagemenu
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_pagemenu_upgrade($oldversion = 0) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($result && $oldversion < 2007091702) {

        // Define field taborder to be added to pagemenu.
        $table = new xmldb_table('pagemenu');
        $field = new xmldb_field('taborder');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'useastab');

        // Launch add field taborder.
        $result = $result and $dbman->add_field($table, $field);
    }

    return $result;
}
