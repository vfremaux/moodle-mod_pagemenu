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
 * @version $Id: link.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 */

defined('MOODLE_INTERNAL') || die();

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
        global $DB;

        $linknamestatus = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'linkname': {
                    // We just want to know that it is there.
                    $linknamestatus = true;
                    break;
                }
                case 'linkurl': {
                    // Eventually we might have to recode some link content...
                    // $DB->update_record('pagemenu_link_data', $datum);
                    break;
                }
                default: {
                    $restorestep->log('Deleting link related unknown data type: '.$datum->name, backup::LOG_ERROR);
                    // Not recognized.
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
                }
            }
        }
    }
}
