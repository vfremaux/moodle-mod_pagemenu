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
 * Version details
 * Code fragment to define the version of this module
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * Changes : 
 * - Allows authorized people to see hidden pages in menu as dimmed links
 **/

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2014030800;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2015111100;  // Requires this Moodle version
$plugin->component = 'mod_pagemenu';  // Name of component
$plugin->cron     = 0;           // Period for cron to check this module (secs)
$plugin->maturity = MATURITY_RC;
$plugin->release = "3.0.0 (Build 2014030800)";
$plugin->dependencies = array('format_page' => 2013012900);

