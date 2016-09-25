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
 * Pagemenu's Local Library
 *
 * @author Moodle 2 Valery Fremaux (valery.fremaux@gmail.com) from code of Mark Nielsen
 * @version Moodle 2.x
 * @package pagemenu
 **/

/**
 * Get the base link class, almost always used
 **/
require_once($CFG->dirroot.'/mod/pagemenu/link_base.class.php');
require_once($CFG->dirroot.'/mod/pagemenu/classes/event/course_module_viewed.php');

/**
 * Get link types
 *
 * @return array
 **/
function pagemenu_get_links() {
    return array('link', 'module', 'page');
}

/**
 * Get an array of link type classes
 *
 * @return array
 **/
function pagemenu_get_link_classes() {
    $return = array();
    foreach(pagemenu_get_links() as $type) {
        $return[$type] = mod_pagemenu_link::factory($type);
    }
    return $return;
}

/**
 * Returns course module, course and module instance.
 *
 * @param int $cmid Course module ID
 * @param int $pagemenuid pagemenu module ID
 * @return array of objects
 */
function pagemenu_get_basics($cmid = 0, $pagemenuid = 0) {
    global $DB;

    if ($cmid) {
        if (!$cm = get_coursemodule_from_id('pagemenu', $cmid)) {
            print_error('invalidcoursemodule');
        }
        if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('coursemisconf');
        }
        if (!$pagemenu = $DB->get_record('pagemenu', array('id' => $cm->instance))) {
            print_error('invalidpagemenuid', 'pagemenu');
        }

    } else if ($pagemenuid) {
        if (!$pagemenu = $DB->get_record('pagemenu', array('id' => $pagemenuid))) {
            print_error('invalidpagemenuid', 'pagemenu');
        }
        if (!$course = $DB->get_record('course', array('id' => $pagemenu->course))) {
            print_error('coursemisconf');
        }
        if (!$cm = get_coursemodule_from_instance('pagemenu', $pagemenu->id, $course->id)) {
            print_error('invalidcoursemodule');
        }

    } else {
        print_error('missingparameter');
    }

    return array($cm, $course, $pagemenu);
}

/**
 * Sets a message to be printed.  Messages are printed
 * by calling {@link pagemenu_print_messages()}.
 *
 * @uses $SESSION
 * @param string $message The message to be printed
 * @param string $class Class to be passed to {@link notify()}.  Usually notifyproblem or notifysuccess.
 * @param string $align Alignment of the message
 * @return boolean
 */
function pagemenu_set_message($message, $class="notifyproblem", $align='center') {
    global $SESSION;

    if (empty($SESSION->messages) or !is_array($SESSION->messages)) {
        $SESSION->messages = array();
    }

    $SESSION->messages[] = array($message, $class, $align);

    return true;
}

/**
 * Link Management Functions
 *
 */

/**
 * Gets the first link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 */
function pagemenu_get_first_linkid($pagemenuid) {
    global $DB;

    return $DB->get_field('pagemenu_links', 'id', array('pagemenuid' => $pagemenuid, 'previd' => 0));
}

/**
 * Gets the last link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 */
function pagemenu_get_last_linkid($pagemenuid) {
    global $DB;

    return $DB->get_field('pagemenu_links', 'id', array('pagemenuid' => $pagemenuid, 'nextid' => 0));
}

/**
 * Append a link to the end of the list
 *
 * @param object $link A link ready for insert with previd/nextid set to 0
 * @param int $previd (Optional) If the last link ID is know, then pass it here  DO NOT PASS ANY OTHER ID!!!
 * @return object
 */
function pagemenu_append_link($link, $previd = NULL) {
    global $DB;

    if ($previd !== NULL) {
        $link->previd = $previd;
    } else if ($lastid = pagemenu_get_last_linkid($link->pagemenuid)) {
        // Add new one after
        $link->previd = $lastid;
    } else {
        $link->previd = 0; // Just make sure.
    }

    if (!$link->id = $DB->insert_record('pagemenu_links', $link)) {
        print_error('errorlinkinsert', 'pagemenu');
    }
    // Update the previous link to look to the new link.
    if ($link->previd) {
        if (!$DB->set_field('pagemenu_links', 'nextid', $link->id, array('id' => $link->previd))) {
            print_error('errorlinkorderupdate', 'pagemenu');
        }
    }

    return $link;
}

/**
 * Deletes a link and all associated data
 * Also maintains ordering
 *
 * @param int $linkid ID of the link to delete
 * @return boolean
 **/
function pagemenu_delete_link($linkid) {
    global $DB;

    pagemenu_remove_link_from_ordering($linkid);

    if (!$DB->delete_records('pagemenu_link_data', array('linkid' => $linkid))) {
        print_error('errorlinkdatadelete', 'pagemenu');
    }
    if (!$DB->delete_records('pagemenu_links', array('id' => $linkid))) {
        print_error('errorlinkdelete', 'pagemenu');
    }
    return true;
}

/**
 * Move a link to a new position in the ordering
 *
 * @param object $pagemenu Page menu instance
 * @param int $linkid ID of the link we are moving
 * @param int $after ID of the link we are moving our link after (can be 0)
 * @return boolean
 **/
function pagemenu_move_link($pagemenu, $linkid, $after) {
    global $DB;

    $link = new stdClass;
    $link->id = $linkid;

    // Remove the link from where it was (Critical: this first!).
    pagemenu_remove_link_from_ordering($link->id);

    if ($after == 0) {
        // Adding to front - get the first link.
        if (!$firstid = pagemenu_get_first_linkid($pagemenu->id)) {
            print_error('errorfirstlinkid', 'pagemenu');
        }
        // Point the first link back to our new front link.
        if (!$DB->set_field('pagemenu_links', 'previd', $link->id, array('id' => $firstid))) {
            print_error('errorlinkorderupdate', 'pagemenu');
        }
        // Set prev/next.
        $link->nextid = $firstid;
        $link->previd = 0;
    } else {
        // Get the after link.
        if (!$after = $DB->get_record('pagemenu_links', array('id' => $after))) {
            print_error('errorlinkid', 'magemenu');
        }
        // Point the after link to our new link.
        if (!$DB->set_field('pagemenu_links', 'nextid', $link->id, array('id' => $after->id))) {
            print_error('errorlinkorderupdate', 'pagemenu');
        }
        // Set the next link in the ordering to look back correctly.
        if ($after->nextid) {
            if (!$DB->set_field('pagemenu_links', 'previd', $link->id, array('id' => $after->nextid))) {
                print_error('errorlinkorderupdate', 'pagemenu');
            }
        }
        // Set next/prev.
        $link->previd = $after->id;
        $link->nextid = $after->nextid;
    }

    if (!$DB->update_record('pagemenu_links', $link)) {
        print_error('errorlinkupdate', 'pagemenu');
    }

    return true;
}

/**
 * Removes a link from the link ordering
 *
 * @param int $linkid ID of the link to remove
 * @return boolean
 **/
function pagemenu_remove_link_from_ordering($linkid) {
    global $DB;

    if (!$link = $DB->get_record('pagemenu_links', array('id' => $linkid))) {
        print_error('errorlinkid', 'pagemenu');
    }
    // Point the previous link to the one after this link.
    if ($link->previd) {
        if (!$DB->set_field('pagemenu_links', 'nextid', $link->nextid, array('id' => $link->previd))) {
            print_error('errorlinkorderupdate', 'pagemenu');
        }
    }
    // Point the next link to the one before this link.
    if ($link->nextid) {
        if (!$DB->set_field('pagemenu_links', 'previd', $link->previd, array('id' => $link->nextid))) {
            print_error('errorlinkorderupdate', 'pagemenu');
        }
    }
    return true;
}

/**
 * Bulk menu builder
 *
 * @param array $pagemenus An array of pagemenu course module records with id, instance and visible set
 * @param boolean $yui Turn YUI Menu support On/Off - If On, then extra divs and classes will be added and full trees are printed
 * @param boolean $menuinfo True, returns menu information object.  False, return menu HTML
 * @param int $courseid ID of the course that the menus belong
 * @return array
 **/
function pagemenu_build_menus($pagemenus, $yui = false, $menuinfo = false, $courseid = NULL) {
    global $COURSE, $PAGE, $DB;

    $renderer = $PAGE->get_renderer('mod_pagemenu');

    if ($courseid === null) {
        $courseid = $COURSE->id;
    }

/// Filter out the menus that the user cannot see.

    $canviewhidden = has_capability('moodle/course:viewhiddenactivities', context_course::instance($courseid));

    // Load all the context instances at once.
    $instances = context_module::instance(array_keys($pagemenus));

    $pagemenuids = array();
    foreach ($pagemenus as $pagemenu) {
        if (has_capability('mod/pagemenu:view', $instances[$pagemenu->id]) and ($pagemenu->visible or $canviewhidden)) {
            $pagemenuids[$pagemenu->id] = $pagemenu->instance;
        }
    }

    if (empty($pagemenuids)) {
        // Cannot see any of them
        return false;
    }

    // Start fetching links and link data for ALL of the menus.
    if (!$links = $DB->get_records_list('pagemenu_links', array('pagemenuid' => implode(',', $pagemenuids)))) {
        // None of the menus have links...
        return false;
    }

    $data = pagemenu_get_link_data($links);

    // Find all the first link IDs - this avoids going to the db for each menu or looping through all links for each module.
    $firstlinkids = array();
    foreach ($links as $link) {
        if ($link->previd == 0) {
            $firstlinkids[$link->pagemenuid] = $link->id;
        }
    }

    $menus = array();
    foreach ($pagemenuids as $cmid => $pagemenuid) {
        $menus[$cmid] = $renderer->build_menu($pagemenuid, false, $yui, $menuinfo, $links, $data, $firstlinkids);
    }

    return $menus;
}

/**
 * Gets link data for all passed links and organizes the records
 * in an array keyed on the linkid.
 *
 * @param array $links An array of links with the keys = linkid
 * @return array
 **/
function pagemenu_get_link_data($links) {
    global $DB;

    $organized = array();

    if (!empty($links)) {
        $idlist = implode(',', array_keys($links));
        if ($data = $DB->get_records_select('pagemenu_link_data', " linkid IN ($idlist) ", array())) {
    
            foreach ($data as $datum) {
                if (!array_key_exists($datum->linkid, $organized)) {
                    $organized[$datum->linkid] = array();
                }

                $organized[$datum->linkid][] = $datum;
            }
        }
    }

    return $organized;
}

/**
 * Helper function to handle edit actions
 *
 * @param object $pagemenu Page menu instance
 * @param string $action Action that is being performed
 * @return boolean If return true, then a redirect will occure (in edit.php at least)
 **/
function pagemenu_handle_edit_action($pagemenu, $action = null) {

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $linkid = required_param('linkid', PARAM_INT);

    if ($action === null) {
        $action = required_param('action', PARAM_ALPHA);
    }

    switch ($action) {
        case 'edit':
        case 'move':
            return false;
            break;

        case 'movehere':
            $after = required_param('after', PARAM_INT);
            pagemenu_move_link($pagemenu, $linkid, $after);
            pagemenu_set_message(get_string('linkmoved', 'pagemenu'), 'notifysuccess');
            break;

        case 'delete':
            pagemenu_delete_link($linkid);
            pagemenu_set_message(get_string('linkdeleted', 'pagemenu'), 'notifysuccess');
            break;

        default:
            print_error('errorinvalidaction', 'pagemenu', $action);
            break;
    }

    return true;
}

/**
 * Prefix class names
 *
 * @param string $class A string of class names separated by spaces
 * @param string $prefix The prefix to attach
 * @return string
 */
function pagemenu_prefix_class_names($class, $prefix = 'yui') {
    $classnames = explode(' ', $class);
    $prefixed = array();
    foreach ($classnames as $classname) {
        $prefixed[] = $prefix.$classname;
    }
    $prefixed = implode(' ', $prefixed);

    return $prefixed;
}
