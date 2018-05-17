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
 * Library of functions and constants for module pagemenu
 *
 * @author Mark Nielsen
 * @reauthor Valery Fremaux for Moodle 2
 * @version $Id: lib.php,v 1.3 2012-06-18 16:08:03 vf Exp $
 * @package pagemenu
 **/

/**
 * This function is not implemented in this plugin, but is needed to mark
 * the vf documentation custom volume availability.
 */
function mod_pagemenu_supports_feature() {
    assert(1);
}

/**
 * List of features supported in pagemenu module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function pagemenu_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return false;
        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted pagemenu record
 */
function pagemenu_add_instance($pagemenu) {
    global $DB;

    pagemenu_process_settings($pagemenu);

    if (!isset($pagemenu->intro)) {
        $pagemenu->intro = '';
        $pagemenu->introformat = FORMAT_HTML;
    }

    return $DB->insert_record('pagemenu', $pagemenu);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 */
function pagemenu_update_instance($pagemenu) {
    global $DB;

    pagemenu_process_settings($pagemenu);
    $pagemenu->id = $pagemenu->instance;

    return $DB->update_record('pagemenu', $pagemenu);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function pagemenu_delete_instance($id) {
    global $DB;

    $result = true;

    if ($links = $DB->get_records('pagemenu_links', array('pagemenuid' => $id), '', 'id')) {
        $linkids = implode(',', array_keys($links));

        $result = $DB->delete_records_select('pagemenu_link_data', "linkid IN($linkids)");

        if ($result) {
            $result = $DB->delete_records('pagemenu_links', array('pagemenuid' => $id));
        }
    }
    if ($result) {
        $result = $DB->delete_records('pagemenu', array('id' => $id));
    }

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @uses $CFG
 * @param object $course  Might not be object :\
 * @param object $user User object
 * @param mixed $mod Don't know
 * @param object $pagemenu pagemenu instance object
 * @return object
 */
function pagemenu_user_outline($course, $user, $mod, $pagemenu) {
    return false;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course  Might not be object :\
 * @param object $user User object
 * @param mixed $mod Don't know
 * @param object $pagemenu pagemenu instance object
 * @return boolean
 */
function pagemenu_user_complete($course, $user, $mod, $pagemenu) {
    return false;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in pagemenu activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses $CFG
 * @return boolean true if anything was printed, otherwise false
 * @todo Finish documenting this function
 */
function pagemenu_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $USER;

    $printed = false;

    return $printed;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 */
function pagemenu_grades($pagemenuid) {
    return null;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of pagemenu. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function pagemenu_get_participants($pagemenuid) {
    return false;
}

/**
 * This function returns if a scale is being used by one pagemenu
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $pagemenuid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function pagemenu_scale_used($pagemenuid, $scaleid) {
    $return = false;

    return $return;
}

/**
 *
 */
function pagemenu_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Any other pagemenu functions go here.  Each of them must have a name that
 * starts with pagemenu_
 */

/**
 * General pagemenu Functions
 *
 */

/**
 * Processes common settings from {@link pagemenu_update_instance}
 * and {@link pagemenu_add_instance}
 *
 * @return void
 */
function pagemenu_process_settings(&$pagemenu) {
    $pagemenu->timemodified = time();
    $pagemenu->taborder     = round(@$pagemenu->taborder, 0);
}

/**
 * This function allows the tool_dbcleaner to register integrity checks
 */
function pagemenu_dbcleaner_add_keys() {
    global $DB;

    $pagemenumoduleid = $DB->get_field('modules', 'id', array('name' => 'pagemenu'));

    $keys = array(
        array('pagemenu', 'course', 'course', 'id', ''),
        array('pagemenu', 'id', 'course_modules', 'instance', ' module = '.$pagemenumoduleid.' '),
        array('pagemenu_links', 'pagemenuid', 'pagemenu', 'id', ''),
        array('pagemenu_link_data', 'linkid', 'pagemenu_links', 'id', ''),
    );

    return $keys;
}