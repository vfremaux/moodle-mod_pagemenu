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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_pagemenu_activity_task
 */

/**
 * Define the complete pagemenu structure for backup, with file and id annotations
 */
class backup_pagemenu_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated

        $pagemenu = new backup_nested_element('pagemenu', array('id'), array('name', 'intro', 'introformat', 'displayname', 'timemodified'));

        $links = new backup_nested_element('links');

        $link = new backup_nested_element('link', array('id'), array('previd', 'nextid', 'type'));

        $data = new backup_nested_element('data');

        $datum = new backup_nested_element('datum', array('id'), array('linkid', 'name', 'value'));

        // Build the tree

        $pagemenu->add_child($links);
        $links->add_child($link);

        $link->add_child($data);
        $data->add_child($datum);

        // Define sources

        $pagemenu->set_source_table('pagemenu', array('id' => backup::VAR_ACTIVITYID));
        $link->set_source_table('pagemenu_links', array('pagemenuid' => backup::VAR_PARENTID));
        $datum->set_source_table('pagemenu_link_data', array('linkid' => backup::VAR_PARENTID));

        // Define file annotations

        $pagemenu->annotate_files('mod_pagemenu', 'intro', null); // This file area hasn't itemid

        // Return the root element (pagemenu), wrapped into standard activity structure
        return $this->prepare_activity_structure($pagemenu);
    }

}
