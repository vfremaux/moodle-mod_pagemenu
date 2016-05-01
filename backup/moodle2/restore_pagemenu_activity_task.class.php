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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pagemenu/backup/moodle2/restore_pagemenu_stepslib.php'); // Because it exists (must)

/**
 * pagemenu restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_pagemenu_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_pagemenu_activity_structure_step('pagemenu_structure', 'pagemenu.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('pagemenu', array('intro'), 'pagemenu');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        // List of pagemenus in course
        $rules[] = new restore_decode_rule('PAGEMENUINDEX', '/mod/pagemenu/index.php?id=$1', 'course');
        // pagemenu by cm->id and pagemenu->id
        $rules[] = new restore_decode_rule('PAGEMENUVIEWBYID', '/mod/pagemenu/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('PAGEMENUVIEWBYINSTANCE', '/mod/pagemenu/view.php?p=$1', 'pagemenu');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * pagemenu logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('pagemenu', 'add', 'view.php?id={course_module}', '{pagemenu}');
        $rules[] = new restore_log_rule('pagemenu', 'update', 'view.php?id={course_module}', '{pagemenu}');
        $rules[] = new restore_log_rule('pagemenu', 'view', 'view.php?id={course_module}', '{pagemenu}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('pagemenu', 'view pagemenus', 'index.php?id={course}', null);

        return $rules;
    }

    /*
    * We need to a posteriori remap some possible module links and recode next/previous fields in links
    *
    */
    /*
    public function after_restore(){
        global $DB;

        $pagemenuid = $this->get_activityid();

        if ($modulelinks = $DB->get_records('pagemenu_links', array('pagemenuid' => $pagemenuid))){
            foreach ($modulelinks as $ml) {

                $ml->previd = $this->get_mappingid('pagemenu_links', $ml->previd);
                $ml->nextid = $this->get_mappingid('pagemenu_links', $ml->nextid);

                if ($ml->type == 'module'){
                    if ($link = $DB->get_record('pagemenu_link_data', array('linkid' => $ml->id))){
                        $link->value = $this->get_mappingid('course_module', $link->value);
                        $DB->update_record('pagemenu_link_data', $link);
                    } else {
                        $this->get_logger()->process("Failed to restore dependency for pagemenu link '$ml->name'. ", backup::LOG_ERROR);                
                    }
                }
                $DB->update_record('pagemenu_links', $ml);
            }
        }
    }
    */

    /**
     * Return the new id of a mapping for the given itemname
     *
     * @param string $itemname the type of item
     * @param int $oldid the item ID from the backup
     * @param mixed $ifnotfound what to return if $oldid wasnt found. Defaults to false
     */
    public function get_mappingid($itemname, $oldid, $ifnotfound = false) {
        $mapping = $this->get_mapping($itemname, $oldid);
        return $mapping ? $mapping->newitemid : $ifnotfound;
    }

    /**
     * Return the complete mapping from the given itemname, itemid
     */
    public function get_mapping($itemname, $oldid) {
        return restore_dbops::get_backup_ids_record($this->plan->get_restoreid(), $itemname, $oldid);
    }
}
