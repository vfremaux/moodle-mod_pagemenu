<?php  // $Id: version.php,v 1.2 2011-07-07 14:03:26 vf Exp $
/**
 * Code fragment to define the version of this module
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * Changes : 
 * - Allows authorized people to see hidden pages in menu as dimmed links
 **/

$module->version  = 2013021600;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2012120300;  // Requires this Moodle version
$module->component = 'mod_pagemenu';  // Name of component
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->maturity = MATURITY_BETA;
$module->release = "2.4.0 (Build 2013021600)";
$module->dependancies = array('format_page' => 2013020702);

