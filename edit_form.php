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
 * Add link item form or
 * if a link object is passed then
 * print the edit form for that single
 * link
 *
 * @author Mark Nielsen
 * @version $Id: edit_form.php,v 1.2 2011-07-07 14:03:25 vf Exp $
 * @package pagemenu
 */

require_once($CFG->libdir.'/formslib.php');

class mod_pagemenu_edit_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'a');
        $mform->setType('a', PARAM_INT);

        if ($this->_customdata !== null) {
            // Print edit form for a single link type.
            $mform->addElement('hidden', 'linkid', $this->_customdata->link->id);
            $mform->setType('linkid', PARAM_INT);

            $mform->addElement('hidden', 'action', 'edit');
            $mform->setType('action', PARAM_ALPHA);

            $mform->addElement('header', $this->_customdata->type, $this->_customdata->get_name());

            $this->_customdata->edit_form_add($mform);

            $this->add_action_buttons();
        } else {
            // Print add form for all link types.
            foreach (pagemenu_get_link_classes() as $link) {
                if ($link->is_enabled()) {
                    $mform->addElement('header', 'link_'.$link->type, get_string($link->type.'s', 'pagemenu'));
                    $link->edit_form_add($mform);
                }
            }

            $this->add_action_buttons(false, get_string('addlinks', 'pagemenu'));
        }
    }
}
