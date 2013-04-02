<?php
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

        get_all_mods($COURSE->id, $mods, $modnames, $modnamesplural, $modnamesusesd);
        $modinfo = unserialize((string)$COURSE->modinfo);

        $modules = array();
        foreach ($mods as $mod) {
            $instancename = urldecode($modinfo[$mod->id]->name);
            $instancename = format_string($instancename, true,  $COURSE->id);

            $modules[$mod->id] = shorten_text($mod->modfullname.': '.$instancename, 28);
        }
        natcasesort($modules);

        // Add our choose option to the front
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
                    // Relink module ID
                    $newid = backup_getid($restore->backup_unique_code, 'course_modules', $datum->value);
                    if (isset($newid->new_id)) {
                        $datum->value = $newid->new_id;
                        $status = update_record('pagemenu_link_data', $datum);
                    }
                    break;
                default:
                    debugging('Deleting unknown data type: '.$datum->name);
                    // Not recognized
                    delete_records('pagemenu_link_data', 'id', $datum->id);
                    break;
            }
        }

        return $status;
    }
}

?>