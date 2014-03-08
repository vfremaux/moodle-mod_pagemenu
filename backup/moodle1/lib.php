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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    mod
 * @subpackage pagemenu
 * @copyright  2011 Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Pagemenu conversion handler
 */
class moodle1_mod_pagemenu_handler extends moodle1_mod_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    /** @var int cmid */
    protected $moduleid = null;


    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'pagemenu', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU',
                array(
                    'newfields' => array(
                        'intro',
                        'introformat'
                    ),
                )
            ),
            new convert_path(
                'pagemenu_links', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU/LINKS',
                array(
                )
            ),
            new convert_path(
                'pagemenu_link', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU/LINKS/LINK',
                array(
                )
            ),
            new convert_path(
                'pagemenu_data', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU/LINKS/LINK/DATA',
                array(
                )
            ),
            new convert_path(
                'pagemenu_datum', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU/LINKS/LINK/DATA/DATUM',
                array(
                )
            ),
       );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/TRACKER
     * data available
     */
    public function process_pagemenu($data) {

        // get the course module id and context id
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid);
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_pagemenu');

        // convert course files embedded into the intro
        $data['intro'] = '';
        $data['introformat'] = FORMAT_MOODLE;

        // write inforef.xml
        $this->open_xml_writer("activities/pagemenu_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();

        // write pagemenu.xml
        $this->open_xml_writer("activities/pagemnu_{$moduleid}/pagemenu.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'pagemenu', 'contextid' => $contextid));

        $this->xmlwriter->begin_tag('pagemenu', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }

        return $data;
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'pagemenu' path
     */
    public function on_pagemenu_end() {

		// flush last pending tmp structure (issues)
		$this->flushtmp();

        // finish writing pagemenu.xml
        $this->xmlwriter->end_tag('pagemenu');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml
        $this->open_xml_writer("activities/pagemenu_{$this->moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }

	/* LINKS */
    public function on_pagemenu_links_start() {
        $this->xmlwriter->begin_tag('links');
    }

    public function on_pagemenu_links_end() {
        $this->xmlwriter->end_tag('links');
    }

	// process link in one single write
    public function process_pagemenu_link($data) {
        $this->write_xml('link', array('id' => $data['id'], 'pagemenuid' => $data['pagemenuid'], 'previd' => $data['previd'], 'nextid' => $data['nextid'], 'type' => $data['type']));
    }

	/* LINKS */
    public function on_pagemenu_data_start() {
        $this->xmlwriter->begin_tag('data');
    }

    public function on_pagemenu_data_end() {
        $this->xmlwriter->end_tag('data');
    }

	// process link in one single write
    public function process_pagemenu_datum($data) {
        $this->write_xml('datum', array('id' => $data['id'], 'linkid' => $data['linkid'], 'name' => $data['name'], 'value' => $data['value']));
    }

}
