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
 * Form to define a new instance of this module or edit an
 * existing instance.  It is used from /course/modedit.php.
 *
 * @version
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package pagemenu
 * @author moodle 2.x valery.fremaux valery.fremaux@gmail.com
 */

defined('MOODLE_INTERNAL') || die();

class mod_pagemenu_mod_form extends moodleform_mod {

    public function definition() {
        $mform =& $this->_form;

        // Our general settings.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('hidden', 'intro'); // Intro mandatory fiedl is not used.
        $mform->setType('intro', PARAM_TEXT);
        $mform->addElement('hidden', 'introformat'); // Introformat mandatory fiedl is not used.
        $mform->setType('introformat', PARAM_INT);

        $mform->addElement('checkbox', 'displayname', get_string('displayname', 'pagemenu'));
        $mform->addHelpButton('displayname', 'displayname', 'pagemenu');

        // Standard mod elements.
        $features = new stdClass();
        $features->groups = false;
        $features->idnumber = false;
        $features->gradecat = false;
        $features->outcomes = false;

        $this->standard_coursemodule_elements($features);

        // Buttons.
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        $mform =& $this->_form;

        // Once form is submitted, check to make sure our checkboxes are set to something.
        if ($this->is_submitted()) {
            $values = &$mform->_submitValues;

            foreach (array('displayname') as $key) {
                if (!isset($values[$key])) {
                    $values[$key] = 0;
                }
            }
        }
    }
}
