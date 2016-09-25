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
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: module.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/pagemenu/link_base.class.php');

/**
 * Link Class Definition - defines
 * properties for link to a module
 */
class mod_pagemenu_link_module extends mod_pagemenu_link {

    public function get_data_names() {
        return array('moduleid');
    }

    public function edit_form_add(&$mform) {
        global $COURSE, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');

        $courseinfo = get_fast_modinfo($COURSE);
        $allcms = $courseinfo->get_cms();
        $modules = array();
        foreach ($allcms as $cminfo) {
            $instancename = format_string($cminfo->name);

            $modules[$cminfo->id] = shorten_text($cminfo->modname.': '.$instancename, 28);
        }
        natcasesort($modules);

        // Add our choose option to the front.
        $options = array(0 => get_string('choose', 'pagemenu')) + $modules;

        $mform->addElement('select', 'moduleid', get_string('addmodule', 'pagemenu'), $options);
        $mform->setType('moduleid', PARAM_INT);
    }

    public function get_menuitem($editing = false, $yui = false) {
        global $CFG, $COURSE;

        if (empty($this->link->id) or empty($this->config->moduleid)) {
            return false;
        }

        $modinfo = get_fast_modinfo($COURSE);

        if (!array_key_exists($this->config->moduleid, $modinfo->cms)) {
            return false;
        }
        $cm = $modinfo->cms[$this->config->moduleid];

        if ($cm->uservisible) {

            $menuitem         = $this->get_blank_menuitem();
            $menuitem->title  = format_string($cm->name, true, $cm->course);
            $menuitem->url    = "$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id";
            $menuitem->active = $this->is_active($menuitem->url);

            if (!$cm->visible) {
                $menuitem->class .= ' dimmed';
            }

            return $menuitem;
        }

        return false;
    }

    public static function after_restore($restorestep, $data, $courseid) {
        global $DB;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'moduleid': {
                    // Relink module ID.
                    $newid = $restorestep->get_mappingid('course_modules', $datum->value);
                    if ($newid) {
                        $datum->value = $newid;
                        $DB->update_record('pagemenu_link_data', $datum);
                    } else {
                        /*
                         * the course module is NOT accessible, nor mappable, so the link must be destroyed.
                         * This happens f.e. when restoring a single pagemenu activity into a distinct course
                         * without importing relevant linked modules.
                         */
                        if (!$DB->get_record('course_modules', array('id' => $datum->value, 'course' => $courseid))) {
                            $DB->delete_records('pagemenu_links', array('id' => $datum->linkid));
                            $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                        }
                    }
                    break;
                }
                default: {
                    $restorestep->log('Deleting module link related unknown data type: '.$datum->name, backup::LOG_ERROR);
                    // Not recognized.
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
                }
            }
        }
    }
}
