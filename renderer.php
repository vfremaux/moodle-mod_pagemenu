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
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

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
     **/
    function header($cm, $course, $pagemenu, $currenttab = 'view', $focus = '', $showtabs = true) {
        global $CFG, $USER, $PAGE, $OUTPUT;

        $strpagemenus = get_string('modulenameplural', 'pagemenu');
        $strpagemenu  = get_string('modulename', 'pagemenu');
        $strname = format_string($pagemenu->name);

    // Log it!
        // add_to_log($course->id, 'pagemenu', $currenttab, "$currenttab.php?id=$cm->id", $strname, $cm->id);
        $params = array(
            'context' => context_module::instance($cm->id),
            'objectid' => $pagemenu->id,
            'other' => array('currenttab' => $currenttab)
        );
        $event = \mod_pagemenu\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
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
        global $CFG;
    
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
    
        foreach($SESSION->messages as $message) {
            $str .= $OUTPUT->notification($message[0], $message[1]);
        }

        // Reset.
        unset($SESSION->messages);

        return $str;
    }
}
