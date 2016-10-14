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
 * Version details.
 *
 * @package     mod_pagemenu
 * @category    mod
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2014030800;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2015050500;  // Requires this Moodle version.
$plugin->component = 'mod_pagemenu';  // Name of component.
$plugin->maturity = MATURITY_RC;
$plugin->release = "2.9.0 (Build 2014030800)";
$plugin->dependencies = array('format_page' => 2016071203);

// Non moodle attributes.
$plugin->codeincrement = '2.9.0001';