<?php
// This file keeps track of upgrades to 
// this module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: module.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 **/

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

        $modulenames = get_module_types_names();
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

    public static function restore_data($data, $restore) {
        $status = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'moduleid':
                    // Relink module ID.
                    $newid = backup_getid($restore->backup_unique_code, 'course_modules', $datum->value);
                    if (isset($newid->new_id)) {
                        $datum->value = $newid->new_id;
                        $status = $DB->update_record('pagemenu_link_data', $datum);
                    }
                    break;
                default:
                    debugging('Deleting unknown data type: '.$datum->name);
                    // Not recognized
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
            }
        }

        return $status;
    }
}
