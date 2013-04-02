<?php  //$Id: upgrade.php,v 1.1 2010/03/03 15:30:10 vf Exp $

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

function xmldb_pagemenu_upgrade($oldversion=0) {

    global $CFG;

    $result = true;

    if ($result && $oldversion < 2007091702) {

    /// Define field taborder to be added to pagemenu
        $table = new XMLDBTable('pagemenu');
        $field = new XMLDBField('taborder');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'useastab');

    /// Launch add field taborder
        $result = $result and add_field($table, $field);
    }

    return $result;
}

?>