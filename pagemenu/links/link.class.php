<?php
/**
 * Link class definition
 *
 * @author Mark Nielsen
 * @version $Id: link.class.php,v 1.1 2010/03/03 15:30:10 vf Exp $
 * @package pagemenu
 **/

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

    public static function restore_data($data, $restore) {
        $linknamestatus = $linkurlstatus = false;

        foreach ($data as $datum) {
            switch ($datum->name) {
                case 'linkname':
                    // We just want to know that it is there
                    $linknamestatus = true;
                    break;
                case 'linkurl':
                    $content = $datum->value;
                    $result  = restore_decode_content_links_worker($content, $restore);
                    if ($result != $content) {
                        $datum->value = $result;
                        if (debugging() and !defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                        $linkurlstatus = $DB->update_record('pagemenu_link_data', $datum);
                    } else {
                        $linkurlstatus = true;
                    }
                    break;
                default:
                    debugging('Deleting unknown data type: '.$datum->name);
                    // Not recognized
                    $DB->delete_records('pagemenu_link_data', array('id' => $datum->id));
                    break;
            }
        }

        return ($linkurlstatus and $linknamestatus);
    }
}
?>