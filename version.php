<?php  // $Id: version.php,v 1.2 2011-07-07 14:03:26 vf Exp $
/**
 * Code fragment to define the version of this module
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @version $Id: version.php,v 1.2 2011-07-07 14:03:26 vf Exp $
 * @patchedversion $Id: version.php,v 1.2 2011-07-07 14:03:26 vf Exp $
 * Changes : 
 * - Allows authorized people to see hidden pages in menu as dimmed links
 **/

$module->version  = 2009030101;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007020200;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>