<?php
function xmldb_videolecture_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = TRUE;

    if ($oldversion < 2021022217) {

        // Define table videolecture to be created.
        $table = new xmldb_table('videolecture');

        // Adding fields to table videolecture.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('introformat', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('attachment', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table videolecture.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for videolecture.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Videolecture savepoint reached.
        upgrade_mod_savepoint(true, 2021022217, 'videolecture');
    }

    return $result;
}
?>