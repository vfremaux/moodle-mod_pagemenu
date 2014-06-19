<?php  // $Id: version.php,v 1.2 2011-07-07 14:03:26 vf Exp $
/**
 * Code fragment to define the version of this module
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * Changes : 
 * - Allows authorized people to see hidden pages in menu as dimmed links
 **/

$plugin->version  = 2014030800;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2013101800;  // Requires this Moodle version
$plugin->component = 'mod_pagemenu';  // Name of component
$plugin->cron     = 0;           // Period for cron to check this module (secs)
$plugin->maturity = MATURITY_BETA;
$plugin->release = "2.5.0 (Build 2014030800)";
$plugin->dependencies = array('format_page' => 2013012900);

