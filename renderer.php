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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package mod_pagemenu
 * @category mod
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/pagemenu/locallib.php');

class mod_pagemenu_renderer extends plugin_renderer_base {

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
     */
    function header($cm, $course, $pagemenu, $currenttab = 'view', $focus = '', $showtabs = true) {
        global $PAGE, $OUTPUT;

        $strname = format_string($pagemenu->name);

        // Log it!
        // Trigger module viewed event.
        $eventparams = array(
            'objectid' => $pagemenu->id,
            'context' => context_module::instance($cm->id),
        );

        $event = \mod_pagemenu\event\course_module_viewed::create($eventparams);
        $event->add_record_snapshot('pagemenu', $pagemenu);
        $event->trigger();

        // Print header, heading, tabs and messages.
        $url = new moodle_url('/mod/pagemenu/view.php', array('id' => $cm->id));
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
            $this->tabs($cm, $currenttab);
        }

        echo $this->messages();
    }

    /**
     * Prints the tabs for the module
     *
     * @return void
     */
    function tabs($cm, $currenttab) {

        if (has_capability('mod/pagemenu:manage', context_module::instance($cm->id))) {
            $tabs = $row = $inactive = array();

            $taburl = new moodle_url('/mod/pagemenu/view.php', array('id' => $cm->id));
            $row[] = new tabobject('view', $taburl, get_string('view', 'pagemenu'));
            $taburl = new moodle_url('/mod/pagemenu/edit.php', array('id' => $cm->id));
            $row[] = new tabobject('edit', $taburl, get_string('edit', 'pagemenu'));

            $tabs[] = $row;

            print_tabs($tabs, $currenttab, $inactive);
        }
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
    function messages() {
        global $SESSION, $OUTPUT;

        $str = '';

        if (empty($SESSION->messages)) {
            // No messages to print.
            return $str;
        }

        foreach ($SESSION->messages as $message) {
            $str .= $OUTPUT->notification($message[0], $message[1]);
        }

        // Reset.
        unset($SESSION->messages);

        return $str;
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
     */
    public function build_menu($pagemenuid, $editing = false, $yui = false, $menuinfo = false, $links = NULL, $data = NULL, $firstlinkids = array()) {
        global $CFG, $OUTPUT, $DB;

        $info = new stdClass;
        $info->html = '';
        $info->menuitems = array();
        $info->active = false;

        // Set links if not already passed.
        if ($links === null) {
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
            if ($data === null) {
                $data = pagemenu_get_link_data($links);
            }

            if ($editing) {
                $action = optional_param('action', '', PARAM_ALPHA);

                if ($action == 'move') {
                    $moveid = required_param('linkid', PARAM_INT);
                    $alt = get_string('movehere');
                    $params = array('a' => $pagemenuid, 'action' => 'movehere', 'linkid' => $moveid, 'sesskey' => sesskey(), 'after' => '%d');
                    $moveurl = new moodle_url('/mod/pagemenu/edit.php', $params);
                    $movewidget = '<a title="'.$alt.'" href="'.$moveurl.'">'.
                                  '<img src="'.$OUTPUT->pix_url('movehere').'" alt="'.$alt.'" /></a>';
                    $move = true;
                } else {
                    $move = false;
                }

                $table = new html_table();
                $table->id = 'edit-table';
                $table->class = 'generaltable';
                $table->width = '90%';
                $table->tablealign  = 'center';
                $table->cellpadding = '5px';
                $table->cellspacing = '0';
                $table->data = array();

                if ($move) {
                    $editurl = new moodle_url('/mod/pagemenu/edit.php', array('a' => $pagemenuid));
                    $table->head = array(get_string('movingcancel', 'pagemenu', $editurl));
                    $table->wrap = array('nowrap');
                    $table->data[] = array(sprintf($movewidget, 0));
                } else {
                    $linktypestr = get_string('linktype', 'pagemenu');
                    $actionsstr = get_string('actions', 'pagemenu');
                    $renderedstr = get_string('rendered', 'pagemenu');
                    $table->head = array($linktypestr, $actionsstr, $renderedstr);
                    $table->align = array('left', 'center', '');
                    $table->size = array('*', '*', '100%');
                    $table->wrap = array('nowrap', 'nowrap', 'nowrap');
                }
            }

            while ($linkid) {
                if (array_key_exists($linkid, $data)) {
                    $datum = $data[$linkid];
                } else {
                    $datum = null;
                }

                $link = $links[$linkid];
                $linkid = $link->nextid;
                $link = mod_pagemenu_link::factory($link->type, $link, $datum);
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
                        $html = $this->menuitems(array($menuitem));
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

                            $params = array('a' => $pagemenuid, 'action' => $widget, 'linkid' => $link->link->id, 'sesskey' => sesskey());
                            $itemurl = new moodle_url('/mod/pagemenu/edit.php', $params);
                            $widgets[] = '<a title="'.$alt.'" href="'.$itemurl.'">'.
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
                $info->html .= $this->menuitems($info->menuitems, 0, $yui);
                $info->html .= $OUTPUT->box_end();
            }
        } else {
            $classes = 'generalbox boxaligncenter boxwidthnarrow centerpara';
            $info->html = $OUTPUT->box(get_string('nolinksinmenu', 'pagemenu'), $classes, 'pagemenu-empty');
        }

        if ($menuinfo) {
            return $info;
        }
        return $info->html;
    }

    /**
     * Given an array of menu item object, this
     * method will build a list
     *
     * @param array $menuitems An array of menu item objects
     * @param int $depth Current depth for nesting lists
     * @param boolean $yui Add extra HTML and classes to support YUI menu
     * @return string
     */
    public function menuitems($menuitems, $depth = 0, $yui = false) {
        // Don't return anything for empty menus.
        if (empty($menuitems)) {
            return '';
        }

        $html = '';
        $first = true;
        $last = false;
        $count = 1;
        $end = count($menuitems);

        foreach ($menuitems as $menuitem) {
            if ($count == $end) {
                $last = true;
            }
            $item = $this->a($menuitem, $yui);
            if ($menuitem->childtree) {
                $item .= $this->menuitems($menuitem->childtree, $depth + 1, $yui);
            }
            $html .= $this->li($item, $depth, $menuitem->active, $first, $last, $yui);

            if ($first) {
                $first = false;
            }
            $count++;
        }

        return $this->ul($html, $depth, $yui);
    }

    /**
     * Wrap content in a ul element
     *
     * @param string $html HTML to be wrapped
     * @param int $depth Current menu depth
     * @param boolean $yui Add extra HTML and classes to support YUI menu
     * @return string
     */
    public function ul($html, $depth, $yui = false) {
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
            $class = pagemenu_prefix_class_names($class);
        }

        $output .= '<ul class="'.$class.'">'.$html.'</ul>';

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
    public function li($html, $depth, $first, $last, $yui = false) {
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
    public function a($menuitem, $yui = false) {
        $menuitem->class .= ' menuitemlabel';

        if ($menuitem->active) {
            $menuitem->class .= ' current';
        }
        if ($yui) {
            $menuitem->class = pagemenu_prefix_class_names($menuitem->class);
        }

        $title = s($menuitem->title);

        return $menuitem->pre.'<a href="'.$menuitem->url.'" title="'.$title.'"
            onclick="this.target=\'_top\'" class="'.$menuitem->class.'">'.$title.'</a>'.$menuitem->post;
    }
}
