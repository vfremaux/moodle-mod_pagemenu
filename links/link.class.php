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

defined('MOODLE_INTERNAL') || die();

/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: link.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 **/
require_once($CFG->dirroot.'/mod/pagemenu/link_base.class.php');

/**
 * Link Class Definition - defines
 * properties for a regular HTML Link
 */
class mod_pagemenu_link_link extends mod_pagemenu_link {

    public function get_data_names() {
        return array('linkname', 'linkurl');
    }

    public function edit_form_add(&$mform) {
        $mform->addElement('text', 'linkname', get_string('linkname', 'pagemenu'), array('size'=>'47'));
        $mform->setType('linkname', PARAM_TEXT);
        $mform->addElement('text', 'linkurl', get_string('linkurl', 'pagemenu'), array('size'=>'47'));
        $mform->setType('linkurl', PARAM_TEXT);
    }

    public function get_menuitem($editing = false, $yui = false) {
        if (empty($this->link->id) or empty($this->config->linkname) or empty($this->config->linkurl)) {
            return false;
        }

        $menuitem         = $this->get_blank_menuitem();
        $menuitem->title  = format_string($this->config->linkname);
        $menuitem->url    = $this->config->linkurl;
        $menuitem->active = $this->is_active($this->config->linkurl);

        return $menuitem;
    }

    public static function after_restore($restorestep, $data, $courseid) {
        $linknamestatus = $linkurlstatus = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'linkname':
                    // We just want to know that it is there.
                    $linknamestatus = true;
                    break;
                case 'linkurl':
                    $content = $datum->value;
                    // Eventually we might have to recode some link content...
                    // $DB->update_record('pagemenu_link_data', $datum);
                    break;
                default:
                    $restorestep->log('Deleting link related unknown data type: '.$datum->name, backup::LOG_ERROR);
                    // Not recognized.
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
            }
        }
    }
}
