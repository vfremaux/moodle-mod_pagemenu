<?php
/**
 * Pagemenu's Local Library
 *
 * @author Mark Nielsen
 * @version $Id: locallib.php,v 1.1 2010/03/03 15:30:09 vf Exp $
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
    return array('link', 'module', 'page', 'ticket');
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
 **/
function pagemenu_get_basics($cmid = 0, $pagemenuid = 0) {
    if ($cmid) {
        if (!$cm = get_coursemodule_from_id('pagemenu', $cmid)) {
            error('Course Module ID was incorrect');
        }
        if (!$course = get_record('course', 'id', $cm->course)) {
            error('Course is misconfigured');
        }
        if (!$pagemenu = get_record('pagemenu', 'id', $cm->instance)) {
            error('Course module is incorrect');
        }

    } else if ($pagemenuid) {
        if (!$pagemenu = get_record('pagemenu', 'id', $pagemenuid)) {
            error('Course module is incorrect');
        }
        if (!$course = get_record('course', 'id', $pagemenu->course)) {
            error('Course is misconfigured');
        }
        if (!$cm = get_coursemodule_from_instance('pagemenu', $pagemenu->id, $course->id)) {
            error('Course Module ID was incorrect');
        }

    } else {
        error('No course module ID or pagemenu ID were passed');
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
    global $CFG, $USER;

    $strpagemenus = get_string('modulenameplural', 'pagemenu');
    $strpagemenu  = get_string('modulename', 'pagemenu');
    $strname      = format_string($pagemenu->name);

/// Log it!
    add_to_log($course->id, 'pagemenu', $currenttab, "$currenttab.php?id=$cm->id", $strname, $cm->id);


/// Print header, heading, tabs and messages
    print_header_simple($strname, $strname, build_navigation('',$cm), $focus, '', true, update_module_button($cm->id, $course->id, $strpagemenu), navmenu($course, $cm));

    print_heading($strname);

    if ($showtabs) {
        pagemenu_print_tabs($cm, $currenttab);
    }

    pagemenu_print_messages();
}

/**
 * Prints the tabs for the module
 *
 * @return void
 **/
function pagemenu_print_tabs($cm, $currenttab) {
    global $CFG;

    if (has_capability('mod/pagemenu:manage', get_context_instance(CONTEXT_MODULE, $cm->id))) {
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
 **/

/**
 * Sets a message to be printed.  Messages are printed
 * by calling {@link pagemenu_print_messages()}.
 *
 * @uses $SESSION
 * @param string $message The message to be printed
 * @param string $class Class to be passed to {@link notify()}.  Usually notifyproblem or notifysuccess.
 * @param string $align Alignment of the message
 * @return boolean
 **/
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
 **/
function pagemenu_print_messages() {
    global $SESSION;

    if (empty($SESSION->messages)) {
        // No messages to print
        return true;
    }

    foreach($SESSION->messages as $message) {
        notify($message[0], $message[1], $message[2]);
    }

    // Reset
    unset($SESSION->messages);

    return true;
}

/**
 * Link Management Functions
 *
 **/

/**
 * Gets the first link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 **/
function pagemenu_get_first_linkid($pagemenuid) {
    return get_field('pagemenu_links', 'id', 'pagemenuid', $pagemenuid, 'previd', 0);
}

/**
 * Gets the last link ID
 *
 * @param int $pagemenuid ID of a pagemenu instance
 * @return mixed
 **/
function pagemenu_get_last_linkid($pagemenuid) {
    return get_field('pagemenu_links', 'id', 'pagemenuid', $pagemenuid, 'nextid', 0);
}

/**
 * Append a link to the end of the list
 *
 * @param object $link A link ready for insert with previd/nextid set to 0
 * @param int $previd (Optional) If the last link ID is know, then pass it here  DO NOT PASS ANY OTHER ID!!!
 * @return object
 **/
function pagemenu_append_link($link, $previd = NULL) {
    if ($previd !== NULL) {
        $link->previd = $previd;
    } else if ($lastid = pagemenu_get_last_linkid($link->pagemenuid)) {
        // Add new one after
        $link->previd = $lastid;
    } else {
        $link->previd = 0; // Just make sure
    }

    if (!$link->id = insert_record('pagemenu_links', $link)) {
        error('Failed to insert link');
    }
    // Update the previous link to look to the new link
    if ($link->previd) {
        if (!set_field('pagemenu_links', 'nextid', $link->id, 'id', $link->previd)) {
            error('Failed to update link order');
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
    pagemenu_remove_link_from_ordering($linkid);

    if (!delete_records('pagemenu_link_data', 'linkid', $linkid)) {
        error('Failed to delete link data');
    }
    if (!delete_records('pagemenu_links', 'id', $linkid)) {
        error('Failed to delete link data');
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
    $link = new stdClass;
    $link->id = $linkid;

    // Remove the link from where it was (Critical: this first!)
    pagemenu_remove_link_from_ordering($link->id);

    if ($after == 0) {
        // Adding to front - get the first link
        if (!$firstid = pagemenu_get_first_linkid($pagemenu->id)) {
            error('Could not find first link ID');
        }
        // Point the first link back to our new front link
        if (!set_field('pagemenu_links', 'previd', $link->id, 'id', $firstid)) {
            error('Failed to update link ordering');
        }
        // Set prev/next
        $link->nextid = $firstid;
        $link->previd = 0;
    } else {
        // Get the after link
        if (!$after = get_record('pagemenu_links', 'id', $after)) {
            error('Invalid Link ID');
        }
        // Point the after link to our new link
        if (!set_field('pagemenu_links', 'nextid', $link->id, 'id', $after->id)) {
            error('Failed to update link ordering');
        }
        // Set the next link in the ordering to look back correctly
        if ($after->nextid) {
            if (!set_field('pagemenu_links', 'previd', $link->id, 'id', $after->nextid)) {
                error('Failed to update link ordering');
            }
        }
        // Set next/prev
        $link->previd = $after->id;
        $link->nextid = $after->nextid;
    }

    if (!update_record('pagemenu_links', $link)) {
        error('Failed to update link');
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
    if (!$link = get_record('pagemenu_links', 'id', $linkid)) {
        error('Invalid Link ID');
    }
    // Point the previous link to the one after this link
    if ($link->previd) {
        if (!set_field('pagemenu_links', 'nextid', $link->nextid, 'id', $link->previd)) {
            error('Failed to update link ordering');
        }
    }
    // Point the next link to the one before this link
    if ($link->nextid) {
        if (!set_field('pagemenu_links', 'previd', $link->previd, 'id', $link->nextid)) {
            error('Failed to update link ordering');
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
    global $CFG;

    $info            = new stdClass;
    $info->html      = '';
    $info->menuitems = array();
    $info->active    = false;

    // Set links if not already passed
    if ($links === NULL) {
        $links = get_records('pagemenu_links', 'pagemenuid', $pagemenuid);
    }
    // Check passed array first, otherwise go to DB
    if (array_key_exists($pagemenuid, $firstlinkids)) {
        $linkid = $firstlinkids[$pagemenuid];
    } else {
        $linkid = pagemenu_get_first_linkid($pagemenuid);
    }

    if (!empty($links) and !empty($linkid)) {

        // Get all link config data if we don't have it already
        if ($data === NULL) {
            $data = pagemenu_get_link_data($links);
        }

        if ($editing) {
            $action = optional_param('action', '', PARAM_ALPHA);

            if ($action == 'move') {
                $moveid     = required_param('linkid', PARAM_INT);
                $alt        = s(get_string('movehere'));
                $movewidget = "<a title=\"$alt\" href=\"$CFG->wwwroot/mod/pagemenu/edit.php?a=$pagemenuid&amp;action=movehere&amp;linkid=$moveid&amp;sesskey=".sesskey().'&amp;after=%d">'.
                              "<img src=\"$CFG->pixpath/movehere.gif\" border=\"0\" alt=\"$alt\" /></a>";
                $move = true;
            } else {
                $move = false;
            }

            $table              = new stdClass;
            $table->id          = 'edit-table';
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

            // Update info
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
                                     "<img src=\"$CFG->pixpath/t/$widget.gif\" height=\"11\" width=\"11\" border=\"0\" alt=\"$alt\" /></a>";
                    }

                    $table->data[] = array($link->get_name(), implode('&nbsp;', $widgets), $html);
                }
            }
        }

        if ($editing) {
            $info->html = print_table($table, true);
        } else {
            $info->html = pagemenu_menuitems_to_html($info->menuitems, 0, $yui);
        }
    } else {
        $info->html = print_box(get_string('nolinksinmenu', 'pagemenu'), 'generalbox boxaligncenter boxwidthnarrow centerpara', 'pagemenu-empty', true);
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

/// Filter out the menus that the user cannot see

    $canviewhidden = has_capability('moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_COURSE, $courseid));

    // Load all the context instances at once
    $instances = get_context_instance(CONTEXT_MODULE, array_keys($pagemenus));

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

/// Start fetching links and link data for ALL of the menus
    if (!$links = get_records_list('pagemenu_links', 'pagemenuid', implode(',', $pagemenuids))) {
        // None of the menus have links...
        return false;
    }

    $data = pagemenu_get_link_data($links);

/// Find all the first link IDs - this avoids going to the db
/// for each menu or looping through all links for each module
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
    $organized = array();

    if ($data = get_records_list('pagemenu_link_data', 'linkid', implode(',', array_keys($links)))) {

        foreach ($data as $datum) {
            if (!array_key_exists($datum->linkid, $organized)) {
                $organized[$datum->linkid] = array();
            }

            $organized[$datum->linkid][] = $datum;
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
function pagemenu_handle_edit_action($pagemenu, $action = NULL) {
    global $CFG;

    if (!confirm_sesskey()) {
        error(get_string('confirmsesskeybad', 'error'));
    }

    $linkid = required_param('linkid', PARAM_INT);

    if ($action === NULL) {
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
            error('Inavlid action: '.$action);
            break;
    }

    return true;
}

/**
 * Menu HTML building Methods
 *
 * These are separate from the link classes to
 * help with conrolling structure and class
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
 **/
function pagemenu_menuitems_to_html($menuitems, $depth = 0, $yui = false) {
    // Don't return anything for empty menus
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
 **/
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
 **/
function pagemenu_li($html, $depth, $active, $first, $last, $yui = false) {
    $class = "menuitem depth$depth";

    if ($active) {
        $class .= ' current';
    }
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
 **/
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
 **/
function pagemenu_prefix_class_names($class, $prefix = 'yui') {
    $classnames = explode(' ', $class);
    $prefixed = array();
    foreach ($classnames as $classname) {
        $prefixed[] = $prefix.$classname;
    }
    $prefixed = implode(' ', $prefixed);

    return $prefixed;
}

?>