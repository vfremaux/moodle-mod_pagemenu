<?php  // $Id: mod_form.php,v 1.3 2012-06-18 16:08:03 vf Exp $
/**
 * Form to define a new instance of this module or edit an 
 * existing instance.  It is used from /course/modedit.php.
 *
 * @version $Id: mod_form.php,v 1.3 2012-06-18 16:08:03 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package pagemenu
 **/

require_once('moodleform_mod.php');

class mod_pagemenu_mod_form extends moodleform_mod {

    function definition() {
        $mform =& $this->_form;

    /// Our general settings
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('hidden', 'intro'); // intro mandatory fiedl is not used
        $mform->setType('intro', PARAM_TEXT);
        $mform->addElement('hidden', 'introformat'); // introformat mandatory fiedl is not used
        $mform->setType('introformat', PARAM_INT);

        $mform->addElement('checkbox', 'displayname', get_string('displayname', 'pagemenu'));
        $mform->addHelpButton('displayname', 'displayname', 'pagemenu');

    /// Standard mod elements
        $features = new stdClass();
        $features->groups = false;
        $features->idnumber = false;
        $features->gradecat = false;
        $features->outcomes = false;

        $this->standard_coursemodule_elements($features);

    /// Buttons
        $this->add_action_buttons();
    }

    function definition_after_data() {
        $mform =& $this->_form;

    /// Once form is submitted, check to make sure our checkboxes are set to something
        if ($this->is_submitted()) {
            $values = &$mform->_submitValues;

            // foreach (array('useastab', 'displayname') as $key) {
            foreach (array('displayname') as $key) {
                if (!isset($values[$key])) {
                    $values[$key] = 0;
                }
            }
        }
    }
}
?>