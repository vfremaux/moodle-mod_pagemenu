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
 * Pagemenu's Local Library
 *
 * @author Moodle 2 Valery Fremaux (valery.fremaux@gmail.com) from code of Mark Nielsen
 * @version Moodle 2.x
 * @package pagemenu
 **/

/**
 * Get the base link class, almost always used
 **/
require_once($CFG->dirroot.'/mod/pagemenu/link.class.php');

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
 * Print the standard header for pagemenu module
 *
 * @uses $CFG
 * @uses $USER tabs.php requires it
 * @param object $cm Course module record object
 * @param object $course Couse record object
 * @param object $pagemenu pagemenu module record object
 * @param string $currenttab File location and tab to be selected
 * @param string $focus Focus
 * @param boolean $showtabs Display tabs yes/no
 * @return void
 **/
function pagemenu_print_header($cm, $course, $pagemenu, $currenttab = 'view', $focus = '', $showtabs = true) {
    global $CFG, $USER, $PAGE, $OUTPUT;

    $strpagemenus = get_string('modulenameplural', 'pagemenu');
    $strpagemenu  = get_string('modulename', 'pagemenu');
    $strname = format_string($pagemenu->name);

// Log it!
    add_to_log($course->id, 'pagemenu', $currenttab, "$currenttab.php?id=$cm->id", $strname, $cm->id);


// Print header, heading, tabs and messages.
    $url = $CFG->wwwroot.'/mod/pagemenu/view.php?id='.$cm->id;
    $context = context_module::instance($cm->id);
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_title($strname);
    $PAGE->set_heading($strname);
    $PAGE->set_cacheable(true);
    $PAGE->set_pagetype('mod-pagemenu-view');
    echo $OUTPUT->header();

    echo $OUTPUT->heading($strname);

    if ($showtabs) {
        pagemenu_print_tabs($cm, $currenttab);
    }

    pagemenu_print_messages();
}

/**
 * Prints the tabs for the module
 *
 * @return void
 */
function pagemenu_print_tabs($cm, $currenttab) {
    global $CFG;

    if (has_capability('mod/pagemenu:manage', context_module::instance($cm->id))) {
        $tabs = $row = $inactive = array();

        $row[] = new tabobject('view', "$CFG->wwwroot/mod/pagemenu/view.php?id=$cm->id", get_string('view', 'pagemenu'));
        $row[] = new tabobject('edit', "$CFG->wwwroot/mod/pagemenu/edit.php?id=$cm->id", get_string('edit', 'pagemenu'));

        $tabs[] = $row;

        print_tabs($tabs, $currenttab, $inactive);
    }
}

/**
 * pagemenu Message Functions
 *
 */

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
 * Print all set messages.
 *
 * See {@link pagemenu_set_message()} for setting messages.
 *
 * Uses {@link notify()} to print the messages.
 *
 * @uses $SESSION
 * @return boolean
 */
function pagemenu_print_messages() {
    global $SESSION, $OUTPUT;

    if (empty($SESSION->messages)) {
        // No messages to print.
        return true;
    }

    foreach($SESSION->messages as $message) {
        echo $OUTPUT->notification($message[0], $message[1]);
    }

    // Reset.
    unset($SESSION->messages);

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
 * Generates a menu
 *
 * @param int $pagemenuid ID of the instance to print
 * @param boolean $editing True if your currently editing the menu
 * @param boolean $yui Turn YUI Menu support On/Off - If On, then extra divs and classes will be added and full trees are printed
 * @param boolean $menuinfo True, returns menu information object.  False, return menu HTML
 * @param array $links All of the links used by this menu
 * @param array $data All of the data for the links used by this menu
 * @param array $firstlinkids This is an array of IDs that are the first link for a pagemenu.  Array keys are pagemenu IDs.
 * @return mixed
 **/
function pagemenu_build_menu($pagemenuid, $editing = false, $yui = false, $menuinfo = false, $links = NULL, $data = NULL, $firstlinkids = array()) {
    global $CFG, $OUTPUT, $DB;

    $info            = new stdClass;
    $info->html      = '';
    $info->menuitems = array();
    $info->active    = false;

    // Set links if not already passed.
    if ($links === NULL) {
        $links = $DB->get_records('pagemenu_links', array('pagemenuid' => $pagemenuid));
    }
    // Check passed array first, otherwise go to DB.
    if (array_key_exists($pagemenuid, $firstlinkids)) {
        $linkid = $firstlinkids[$pagemenuid];
    } else {
        $linkid = pagemenu_get_first_linkid($pagemenuid);
    }
    
    if (!empty($links) and !empty($linkid)) {

        // Get all link config data if we don't have it already.
        if ($data === NULL) {
            $data = pagemenu_get_link_data($links);
        }

        if ($editing) {
            $action = optional_param('action', '', PARAM_ALPHA);

            if ($action == 'move') {
                $moveid     = required_param('linkid', PARAM_INT);
                $alt        = s(get_string('movehere'));
                $movewidget = "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/pagemenu/edit.php?a=$pagemenuid&action=movehere&linkid=$moveid&sesskey=".sesskey().'&after=%d">'.
                              "<img src=\"".$OUTPUT->pix_url('movehere')."\" border=\"0\" alt=\"$alt\" /></a>";
                $move = true;
            } else {
                $move = false;
            }

            $table              = new html_table();
            $table->id          = 'edit-table';
            $table->class       = 'generaltable';
            $table->width       = '90%';
            $table->tablealign  = 'center';
            $table->cellpadding = '5px';
            $table->cellspacing = '0';
            $table->data        = array();

            if ($move) {
                $table->head  = array(get_string('movingcancel', 'pagemenu', "$CFG->wwwroot/mod/pagemenu/edit.php?a=$pagemenuid"));
                $table->wrap  = array('nowrap');
                $table->data[] = array(sprintf($movewidget, 0));

            } else {
                $table->head  = array(get_string('linktype', 'pagemenu'), get_string('actions', 'pagemenu'), get_string('rendered', 'pagemenu'));
                $table->align = array('left', 'center', '');
                $table->size  = array('*', '*', '100%');
                $table->wrap  = array('nowrap', 'nowrap', 'nowrap');
            }
        }

        while ($linkid) {
            if (array_key_exists($linkid, $data)) {
                $datum = $data[$linkid];
            } else {
                $datum = NULL;
            }

            $link     = $links[$linkid];
            $linkid   = $link->nextid;
            $link     = mod_pagemenu_link::factory($link->type, $link, $datum);
            $menuitem = $link->get_menuitem($editing, $yui);

            // Update info.
            if ($link->active) {
                $info->active = true;
            }

            if ($menuitem) {
                $info->menuitems[] = $menuitem;
            }
            
            if ($editing) {
                if (!$menuitem) {
                    $html = get_string('linkitemerror', 'pagemenu');
                } else {
                    $html = pagemenu_menuitems_to_html(array($menuitem));
                }

                if ($move) {
                    if ($moveid != $link->link->id) {
                        $table->data[] = array($html);
                        $table->data[] = array(sprintf($movewidget, $link->link->id));
                    }
                } else {
                    $widgets = array();
                    foreach (array('move', 'edit', 'delete') as $widget) {
                        $alt = s(get_string($widget));

                        $widgets[] = "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/pagemenu/edit.php?a=$pagemenuid&amp;action=$widget&amp;linkid={$link->link->id}&amp;sesskey=".sesskey().'">'.
                                     "<img src=\"".$OUTPUT->pix_url("t/$widget")."\" height=\"11\" width=\"11\" border=\"0\" alt=\"$alt\" /></a>";
                    }

                    $table->data[] = array($link->get_name(), implode('&nbsp;', $widgets), $html);
                }
            }
        }

        if ($editing) {
            $info->html = html_writer::table($table, true);
        } else {
            $info->html = $OUTPUT->box_start();
            $info->html .= pagemenu_menuitems_to_html($info->menuitems, 0, $yui);
            $info->html .= $OUTPUT->box_end();
        }
    } else {
        $info->html = $OUTPUT->box(get_string('nolinksinmenu', 'pagemenu'), 'generalbox boxaligncenter boxwidthnarrow centerpara', 'pagemenu-empty');
    }

    if ($menuinfo) {
        return $info;
    }
    return $info->html;
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
    global $COURSE;

    if ($courseid === NULL) {
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
        $menus[$cmid] = pagemenu_build_menu($pagemenuid, false, $yui, $menuinfo, $links, $data, $firstlinkids);
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
    global $CFG;

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
 * Menu HTML building Methods
 *
 * These are separate from the link classes to
 * help with controlling structure and class
 * names.
 */

/**
 * Given an array of menu item object, this
 * method will build a list
 *
 * @param array $menuitems An array of menu item objects
 * @param int $depth Current depth for nesting lists
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 */
function pagemenu_menuitems_to_html($menuitems, $depth = 0, $yui = false) {
    // Don't return anything for empty menus.
    if (empty($menuitems)) {
        return '';
    }

    $html  = '';
    $first = true;
    $last  = false;
    $count = 1;
    $end   = count($menuitems);

    foreach ($menuitems as $menuitem) {
        if ($count == $end) {
            $last = true;
        }
        $item = pagemenu_a($menuitem, $yui);
        if ($menuitem->childtree) {
            $item .= pagemenu_menuitems_to_html($menuitem->childtree, $depth+1, $yui);
        }
        $html .= pagemenu_li($item, $depth, $menuitem->active, $first, $last, $yui);

        if ($first) {
            $first = false;
        }
        $count++;
    }

    return pagemenu_ul($html, $depth, $yui);
}

/**
 * Wrap content in a ul element
 *
 * @param string $html HTML to be wrapped
 * @param int $depth Current menu depth
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 */
function pagemenu_ul($html, $depth, $yui = false) {
    if ($depth == 0) {
        $class = 'menutree';
    } else {
        $class = "childtree depth$depth";
    }

    $output = '';

    if ($yui) {
        if ($depth != 0) {
            // Cannot have this div on root list
            $output .= '<div class="yuimenu">';
        }
        $output .= '<div class="bd">';
        $class   = pagemenu_prefix_class_names($class);
    }

    $output .= "<ul class=\"$class\">$html</ul>";

    if ($yui) {
        $output .= '</div>';

        if ($depth != 0) {
            $output .= '</div>';
        }
    }
    $output .= "\n";

    return $output;
}

/**
 * Wrap content in a list element
 *
 * @param string $html HTML to be wrapped
 * @param int $depth Current menu depth
 * @param boolean $first This is the first list item
 * @param boolean $last This is the last list item
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 */
function pagemenu_li($html, $depth, $first, $last, $yui = false) {
    $class = "menuitem depth$depth";

    if ($last) {
        $class .= ' lastmenuitem';
    }
    if ($first) {
        $class .= ' firstmenuitem';
    }
    if ($yui) {
        $class = pagemenu_prefix_class_names($class);
    }

    return "<li class=\"$class\">$html</li>\n";
}

/**
 * Build a link tag from a menu item
 *
 * @param object $menuitem Menu item object
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 */
function pagemenu_a($menuitem, $yui = false) {
    $menuitem->class .= ' menuitemlabel';

    if ($menuitem->active) {
        $menuitem->class .= ' current';
    }
    if ($yui) {
        $menuitem->class = pagemenu_prefix_class_names($menuitem->class);
    }

    $title = s($menuitem->title);

    return "$menuitem->pre<a href=\"$menuitem->url\" title=\"$title\" onclick=\"this.target='_top'\" class=\"$menuitem->class\">$title</a>$menuitem->post";
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
