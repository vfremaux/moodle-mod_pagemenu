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

defined('MOODLE_INTERNAL') || die();

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_pagemenu_activity_task
 */

/**
 * Structure step to restore one pagemenu activity
 */
class restore_pagemenu_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('pagemenu', '/activity/pagemenu');
        $paths[] = new restore_path_element('link', '/activity/pagemenu/links/link');
        $paths[] = new restore_path_element('link_data', '/activity/pagemenu/links/link/data/datum');
        /*
        if ($userinfo) {
            // No user info.
        }
        */

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_pagemenu($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('pagemenu', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Overrides standard function to force a new idnumber on the instance
     */
    function apply_activity_instance($newitemid) {
        global $DB;

        parent::apply_activity_instance($newitemid);
        // Generate a unique idnumber to discriminate it on GUI.
        $DB->set_field('course_modules', 'idnumber', 'REST'.$newitemid, array('id' => $this->task->get_moduleid()));
    }

    /**
     *
     *
     */
    protected function process_link($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->pagemenuid = $this->get_new_parentid('pagemenu');

        $newitemid = $DB->insert_record('pagemenu_links', $data);
        $this->set_mapping('pagemenu_links', $oldid, $newitemid);
    }

    /**
     *
     *
     */
    protected function process_link_data($data) {
        global $DB;

        $data = (object)$data;

        // See what needs to be remapped depending on parent's type.
        $data->linkid = $this->get_mappingid('pagemenu_links', $data->linkid);

        $newitemid = $DB->insert_record('pagemenu_link_data', $data);
    }

    // We shall have to remap all internal bindings of all generated links.
    protected function after_restore() {
        global $DB, $CFG;

        $conds = array('backupid' => $this->get_restoreid(), 'itemname' => 'pagemenu');

        $mappings = $DB->get_records('backup_ids_temp', $conds);

        if ($mappings) {
            foreach ($mappings as $mapping) {
                if ($alllinks = $DB->get_records('pagemenu_links', array('pagemenuid' => $mapping->newitemid))) {
                    foreach ($alllinks as $link) {
                        // Each link type might remap what it needs.
                        require_once($CFG->dirroot.'/mod/pagemenu/links/'.$link->type.'.class.php');
                        $linkclass = 'mod_pagemenu_link_'.$link->type;
                        $linkdata = $DB->get_records('pagemenu_link_data', array('linkid' => $link->id));
                        $this->log("About to remap link ".$link->id, backup::LOG_ERROR);
                        $linkclass::after_restore($this, $linkdata, $this->get_courseid());
                    }
                }
            }
        }
    }

    // Remap link chaining.
    protected function after_execute() {
        global $DB;

        $newpagemenuid = $this->get_new_parentid('pagemenu');

        if ($alllinks = $DB->get_records('pagemenu_links', array('pagemenuid' => $newpagemenuid))) {
            foreach ($alllinks as $link) {
                $this->log('Remapping prev/next sequence in pagemenu_'.$newpagemenuid.':link_'.$link->id, backup::LOG_ERROR);
                $link->previd = $this->get_mappingid('pagemenu_links', $link->previd);
                $link->nextid = $this->get_mappingid('pagemenu_links', $link->nextid);
                $DB->update_record('pagemenu_links', $link);
            }
        }

        // Add pagemenu related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_pagemenu', 'intro', null);
    }
}
