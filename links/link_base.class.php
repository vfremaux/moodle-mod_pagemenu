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
 * @version $Id: link_base.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 **/

/**
 * Base link class
 */
class link_base {

    /**
     * Editing flag
     *
     * @var boolean
     **/
    public $editing = false;

    /**
     * YUI support flag
     *
     * @var boolean
     **/
    public $yui = false;

    /**
     * Is the link active
     *
     * @var boolean
     **/
    public $active = false;

    /**
     * PHP5 Constructor
     *
     * @return void
     **/
    public function __construct($link = NULL) {
        $this->link_base($link);
    }

    /**
     * Constructor
     *
     * @return void
     **/
    public function link_base($link = NULL) {
        global $CFG;

        $this->type = get_class($this);

        if (is_int($link)) {
            if (!$this->link = $DB->get_record('pagemenu_links', array('id' => $link))) {
                print_error('errorlink', 'pagemenu');
            }
        } else if (is_object($link)) {
            $this->link = $link;
        } else {
            $this->link             = new stdClass;
            $this->link->id         = 0;
            $this->link->pagemenuid = 0;
            $this->link->previd     = 0;
            $this->link->nextid     = 0;
            $this->link->type       = $this->type;
        }

        if ($this->link->id) {
            $this->config = $this->get_data();
        }
    }

    /**
     * Returns the display name of the link
     *
     * @return string
     **/
    public function get_name() {
        return get_string($this->type, 'pagemenu');
    }

    /**
     * Add an element to the
     * edit_form to add a link
     *
     * @param object $mform The Moodle Form Class
     * @return void
     **/
    public function edit_form_add(&$mform) {
        print_error('errorimpl', 'pagemenu', 'edit_form_add()');
    }

    /**
     * Save form data from creating
     * a new link
     *
     * @param object $data Form data (cleaned)
     * @return mixed
     **/
    public function save($data) {
        $names = $this->get_data_names();

        $allset = true;
        foreach ($names as $name) {
            if (empty($data->$name)) {
                $allset = false;
                break;
            }
        }
        if ($allset) {
            if (!empty($data->linkid)) {
                $linkid = $data->linkid;
            } else {
                $linkid = $this->add_new_link($data->a);
            }
            foreach ($names as $name) {
                $this->save_data($linkid, $name, $data->$name);
            }
        }
    }

    /**
     * Create a new link
     *
     * @param int $pagemenuid Instance ID
     * @return int
     **/
    public function add_new_link($pagemenuid) {
        $link             = new stdClass;
        $link->type       = $this->type;
        $link->previd     = 0;
        $link->nextid     = 0;
        $link->pagemenuid = $pagemenuid;

        $link = pagemenu_append_link($link);

        return $link->id;
    }

    /**
     * Get the names of the link data items
     * This allows for the auto processing of 
     * simple data items.
     *
     * @return array
     **/
    public function get_data_names() {
        return array();
    }

    /**
     * Save a piece of link data
     *
     * @param int $linkid ID of the link that the data belongs to
     * @param string $name Name of the data
     * @param mixed $value Value of the data
     * @param boolean $unique Is the name/value combination unique?
     * @return int
     **/
    public function save_data($linkid, $name, $value, $unique = false) {
        global $DB;

        $return = false;

        $data         = new stdClass;
        $data->linkid = $linkid;
        $data->name   = $name;
        $data->value  = $value;

        if ($unique) {
            $fieldname  = 'value';
            $fieldvalue = $data->value;
        } else {
            $fieldname = $fieldvalue = '';
        }

        if ($id = $DB->get_field('pagemenu_link_data', 'id', array('linkid' => $linkid, 'name' => $name, $fieldname => $fieldvalue))) {
            $data->id = $id;
            if ($DB->update_record('pagemenu_link_data', $data)) {
                $return = $id;
            }
        } else {
            $return = $DB->insert_record('pagemenu_link_data', $data);
        }

        return $return;
    }

    /**
     * Gets all of the of the data associated
     * with the link
     *
     * @param int $linkid (Optional) ID of the link
     * @return object
     **/
    function get_data($linkid = NULL) {
        global $DB;

        if ($linkid === NULL) {
            if (empty($this->link->id)) {
                print_error('errorlinkid', 'pagemenu');
            }
            $linkid = $this->link->id;
        }
        $names = $this->get_data_names();

        $data = new stdClass;

        foreach ($names as $name) {
            $data->$name = $DB->get_field('pagemenu_link_data', 'value', array('linkid' => $linkid, 'name' => $name));
        }

        return $data;
    }

    /**
     * Create a menu item that will be used to contruct the menu HTML
     *
     * @param boolean $editing Editing is turned on
     * @param boolean $yui Print with YUI Menu support
     * @return object
     **/
    function get_menuitem($editing = false, $yui = false) {
        print_error('errorimpl', 'pagemenu', 'get_menuitem()');
    }

    /**
     * Returns a blank menu item
     *
     * @return object
     **/
    function get_blank_menuitem() {
        $menuitem            = new stdClass;
        $menuitem->title     = '';
        $menuitem->url       = '';
        $menuitem->class     = 'link_'.get_class($this);
        $menuitem->pre       = '';
        $menuitem->post      = '';
        $menuitem->active    = false;
        $menuitem->childtree = false;

        return $menuitem;
    }

    /**
     * The link can create its own edit actions.
     * Handle them using this method.
     *
     * @return mixed
     **/
    function handle_action() {
        // Nothing
    }

    /**
     * Mostly an internal method to see if the
     * current link is active
     *
     * @param string $url URL to test - see if it is the current page
     * @return boolean
     **/
    function is_active($url = NULL) {
        if ($url === NULL) {
            return false;
        } else if (strpos(qualified_me(), $url) === false) {
            return false;
        } else {
            $this->active = true;
            return true;
        }
    }

    /**
     * Whether or not this link type is enabled
     *
     * @return boolean
     **/
    function is_enabled() {
        return true;
    }

    /**
     * Restore link data - return boolean!
     *
     * This function is called statically, so no use of $this!
     *
     * @param array $data An array of pagemenu_link_data record objects
     * @param object $restore Restore object
     * @return boolean
     **/
    function restore_data($link, $restore) {
        return true;
    }
}
