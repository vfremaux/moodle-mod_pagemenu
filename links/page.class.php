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
 * Link Page class definition
 *
 * @package mod_pagemenu
 * @category mod
 * @author Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/page/lib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');
require_once($CFG->dirroot.'/mod/pagemenu/link_base.class.php');

/**
 * Link Class Definition - defines
 * properties for a link to a page
 * format page or tree
 */
class mod_pagemenu_link_page extends mod_pagemenu_link {

    /**
     * Current Page ID
     *
     * @var int
     */
    protected $currentpageid = null;

    public function get_data_names() {
        return array('pageid');
    }

    public function edit_form_add(&$mform) {
        global $COURSE;

        $pages = array();
        if ($pages = course_page::get_all_pages($COURSE->id, 'flat')) {
            $options = array(0 => get_string('choose', 'pagemenu'));
            $options += $this->build_select_menu($pages);

            $mform->addElement('select', 'pageid', get_string('addpage', 'pagemenu'), $options);
            $mform->setType('pageid', PARAM_INT);
        }
    }

    /**
     * Needs to handle exludes
     */
    protected function get_config($data) {
        global $DB;

        $config = new stdClass;
        $config->exclude = array();

        if (!empty($this->link->id)) {
            if ($data !== null or $data = $DB->get_records('pagemenu_link_data', array('linkid' => $this->link->id))) {

                foreach ($data as $datum) {
                    if ($datum->name == 'exclude') {
                        $config->{$datum->name}[$datum->id] = $datum->value;
                    } else {
                        $config->{$datum->name} = $datum->value;
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Handles the page link item hide/show
     *
     * @return void
     */
    public function handle_action() {
        $showhide = required_param('showhide', PARAM_ALPHA);
        $pageid   = required_param('pageid', PARAM_INT);
        $linkid   = required_param('linkid', PARAM_INT);

        if ($showhide == 'hide') {
            $this->save_data($linkid, 'exclude', $pageid, true);
            pagemenu_set_message(get_string('pagelinkhidden', 'pagemenu'), 'notifysuccess');
        } else if ($showhide == 'show') {
            $DB->delete_records('pagemenu_link_data', array('linkid' => $linkid, 'name' => 'exclude', 'value' => $pageid));
            pagemenu_set_message(get_string('pagelinkvisible', 'pagemenu'), 'notifysuccess');
        } else {
            print_error('errorshowhide', 'pagemenu');
        }
    }

    public function get_menuitem($editing = false, $yui = false) {
        if (empty($this->link->id) or empty($this->config->pageid)) {
            return false;
        }
        if (!$page = course_page::get($this->config->pageid)) {
            // Probably deleted :(.
            return false;
        }

        // Set editing to avoid passing it everywhere.
        $this->editing = $editing;
        $this->yui     = $yui;

        // Load the page with child tree(s).
        $page->get_children();

        // Generate menu item tree.
        return $this->page_to_menuitem($page);
    }

    /**
     * A more complicated and confusing method.
     *
     * Depending on editing on/off, output differs.
     *   Editing on: print all trees except for those whose parent is excluded.
     *   Editing off: Print with all trees collapsed except for the one that is active.
     *
     * @param object $page Page Format Object with child tree set
     * @return string
     */
    protected function page_to_menuitem($page) {
        global $OUTPUT;

        $cm = get_coursemodule_from_instance('pagemenu', $this->link->pagemenuid);
        if ($this->dont_display($page) &&
                !has_capability('mod/pagemenu:viewhidden', context_module::instance($cm->id), null, false)) {
            return false;
        }

        $widget = '';

        if ($this->editing and $page->id != $this->config->pageid) {
            // Show hide/show widgets for children only.
            if ($this->is_excluded($page)) {
                $pix = 'show';
                $alt = get_string('show');
            } else {
                $pix = 'hide';
                $alt = get_string('hide');
            }
            // WOOT - longest URL ever :P.
            $params = array('a' => $this->link->pagemenuid, 'linkid' => $this->link->id,
                'linkaction' => 'page', 'pageid' => $page->id, 'showhide' => $pix, 'sesskey' => sesskey());
            $editurl = new moodle_url('/mod/pagemenu/edit.php', $params);
            $widget = '<a href="'.$editurl.'">
                       <img src="'.$OUTPUT->pix_url("t/$pix").'" alt="'.$alt.'" /></a>&nbsp;';
        } else if ($this->is_excluded($page)) {
            // Excluded
            return false;
        }

        // Build the menu item.
        $menuitem         = $this->get_blank_menuitem();
        $menuitem->title  = $page->get_name();
        $menuitem->pre    = $widget;
        $menuitem->active = $this->is_current($page);
        $menuitem->class = ($this->dont_display($page)) ? 'dimmed' : '';
        $menuitem->disabled = !$page->check_activity_lock();

        if ($page->courseid == SITEID) {
            $menuitem->url = new moodle_url('/index.php', array('page' => $page->id));
        } else {
            $menuitem->url = new moodle_url('/course/view.php', array('id' => $page->courseid, 'page' => $page->id));
        }

        // Deal with children, always a pain :P.
        $children = $page->get_children();
        if (!empty($children) and !$this->is_excluded($page) and ($this->editing or $this->yui or $this->is_active($page))) {
            /*
             * First, we have children
             * AND the current page is not excluded, so we can print children
             * AND we are either editing, printing yui menu or it is active (all reasons to print children)
             */
            $menuitem->childtree = $this->pages_to_menuitems($children);
        }

        // Determine if we display this as a active or inactive parent.
        if (!empty($menuitem->childtree) and !$this->is_excluded($page)) {
            if ($this->editing or $this->is_active($page)) {
                $active = 'active';
            } else {
                $active = 'inactive';
            }
            $menuitem->class .= " parent $active";

            if (!$this->yui) {  // YUI has its own image.
                $pixurl = $OUTPUT->pix_url($active, 'pagemenu');
                $menuitem->post = '&nbsp;<img class="'.$active.'" src="'.$pixurl.'" alt="'.get_string($active, 'pagemenu').'" />';
            }
        }

        return $menuitem;
    }

    /**
     * Converts an array of page objects
     * into an array of menu items
     *
     * @param array $pages Array of page objects
     * @return string
     **/
    protected function pages_to_menuitems($pages) {
        $menuitems = array();
        foreach ($pages as $page) {
            if ($menuitem = $this->page_to_menuitem($page)) {
                $menuitems[] = $menuitem;
            }
        }

        return $menuitems;
    }

    /**
     * Helper method to clean up code
     *
     * Determines if the current page is active,
     * meaning it should display its children if
     * it has any.
     *
     * @param int $pageid Page ID
     * @return boolean
     **/
    protected function is_active($page = null) {
        static $parents = null;

        if ($this->is_current($page)) {
            // It is the current page.
            $this->active = true;
            return true;
        } else if ($parents === null) {
            // Not current page, see if this page is one of the parents of an active child page.
            if (!empty($this->currentpageid)) {
                $parents = $page->get_parents();
            } else {
                $parents = array();
            }
        }

        if (in_array($page->id, array_keys($parents))) {
            $this->active = true;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Compared passed ID to see if it
     * is the current page ID in the course
     *
     * @param int $pageid Page ID
     * @return boolean
     */
    protected function is_current(&$page) {
        if ($this->currentpageid === null) {
            if ($currentpage = course_page::get_current_page(0, false)) {
                $this->currentpageid = $currentpage->id;
            } else {
                $this->currentpageid = 0;
            }
        }

        if ($page->id == $this->currentpageid) {
            $this->active = true;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks to see if the passed page
     * has been excluded.
     *
     * @param object $page Page object
     * @return boolean
     **/
    protected function is_excluded(&$page) {
        return in_array($page->id, $this->config->exclude);
    }

    /**
     * Checks to see if the page has the proper
     * display value to be included into the menu
     * rendering.
     *
     * The page must be pusblished and be set to display
     * in the course menu.
     *
     * @param object $page Page object
     * @return boolean
     **/
    protected function dont_display(&$page) {

        if ($page->displaymenu && $page->is_visible()) {
            return false;
        }
        return true;
    }

    /**
     * This link type is only enabled if the current course
     * is set to the page format.
     *
     * @return boolean
     **/
    public function is_enabled() {
        global $COURSE;

        return ($COURSE->format == 'page');
    }

    /**
     * Builds an options array for pages and their children
     *
     * @param array $pages An array of pages with their children
     * @return array
     **/
    protected function build_select_menu($pages) {

        $options = array();

        foreach ($pages as $page) {

            // Build the name string - first add white space.
            $depth = $page->get_page_depth();

            $options[$page->id] = str_repeat('&nbsp;&nbsp;', $depth);
            if ($depth > 0) {
                // Add a hyphen before all child pages.
                $options[$page->id] .= '-&nbsp;';
            }

            // Add the actual name.
            $name = shorten_text($page->get_name($page), 45);
            if ($this->dont_display($page)) {
                $name = '('.$name.')';
            }
            $options[$page->id] .= $name;

            if (!empty($page->children)) {
                $options = $options + $this->build_select_menu($page->children);
            }
        }
        return $options;
    }

    /**
     * Probably obsolete or to be written elsewhere
     */
    public static function after_restore($restorestep, $data, $courseid) {
        global $DB;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'pageid':
                    // Relink page ID.
                    $newid = $restorestep->get_mappingid('format_page', $datum->value);
                    if ($newid) {
                        $datum->value = $newid;
                        $restorestep->log('Remapping page link_'.$datum->linkid.':linkdata_'.$datum->id.' to '.$newid, backup::LOG_ERROR);
                        $DB->update_record('pagemenu_link_data', $datum);
                    } else {
                        // We could not remap, probably we have no format_pages in the backup,
                        // We just check the page exists and is in this course. Discard if it is not linkable.
                        if (!$DB->get_record('format_page', array('id' => $datum->value, 'courseid' => $courseid))) {
                            $message = 'Failed remap page '.$datum->value.' ';
                            $message .= 'cleaning out page link_'.$datum->linkid.':linkdata_'.$datum->id;
                            $restorestep->log($message, backup::LOG_ERROR);
                            $DB->delete_records('pagemenu_links', array('id' => $datum->linkid));
                            $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                        }
                    }
                    break;

                case 'exclude':
                    // Relink page ID - do not care about failures here.
                    $newid = $restorestep->get_mappingid('format_page', $datum->value);
                    if ($newid) {
                        $restorestep->log('Remapping exclude link_'.$datum->linkid.':linkdata_'.$datum->id.' to '.$newid, backup::LOG_ERROR);
                        $datum->value = $newid;
                        $DB->update_record('pagemenu_link_data', $datum);
                    } else {
                        // Failed, remove it and link.
                        if (!$DB->get_record('format_page', array('id' => $datum->value, 'courseid' => $courseid))) {
                            $message = 'Failed remap page '.$datum->value.' ';
                            $message .= 'cleaning out page link_'.$datum->linkid.':linkdata_'.$datum->id;
                            $restorestep->log($message, backup::LOG_ERROR);
                            $DB->delete_records('pagemenu_links', array('id' => $datum->linkid));
                            $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                        }
                    }
                    break;

                default:
                    $restorestep->log('Deleting page related unknown data type: '.$datum->name, backup::LOG_ERROR);
                    // Not recognized.
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
            }
        }
    }
}
