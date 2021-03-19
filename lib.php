<?php

defined('MOODLE_INTERNAL') || die;

define('VL_FILEMANAGER_MAXBYTES', 1073741824, true);
define('VL_FILEMANAGER_MAXFILES', 1, true);
define('VL_FILEMANAGER_AREAMAXBYTES', VL_FILEMANAGER_MAXBYTES * VL_FILEMANAGER_MAXFILES, true);
define('VL_FILEMANAGER_SUBDIRS', false, true);
define('VL_FILEMANAGER_ACCEPTED_TYPES', array('video'), true);

require_once(__DIR__ . '/../../config.php');

/**
 * List of features supported in Page module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function videolecture_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function videolecture_reset_userdata($data) {
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function videolecture_get_view_actions() {
    return array('view','view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function videolecture_get_post_actions() {
    return array('update', 'add');
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $videolecture
 * @return bool|int
 */
function videolecture_add_instance($videolecture) {
    global $DB;

    $videolecture->timecreated = time();
    $videolecture->timemodified = time();

    $id = $DB->insert_record('videolecture', $videolecture);

    $context = context_module::instance($videolecture->coursemodule);

    $attachmentoptions = array('subdirs' => VL_FILEMANAGER_SUBDIRS, 'maxbytes' => VL_FILEMANAGER_MAXBYTES, 'areamaxbytes' => VL_FILEMANAGER_AREAMAXBYTES,
        'maxfiles' => VL_FILEMANAGER_MAXFILES, 'accepted_types' => VL_FILEMANAGER_ACCEPTED_TYPES, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL);

    $videolecture = file_postupdate_standard_filemanager($videolecture, 'attachment', $attachmentoptions, $context, 'mod_videolecture',
        'attachment', $id);
    $videolecture->id = $id;
    $DB->update_record('videolecture', $videolecture);

    $completiontimeexpected = !empty($videolecture->completionexpected) ? $videolecture->completionexpected : null;
    \core_completion\api::update_completion_date_event($videolecture->coursemodule, 'videolecture', $id, $completiontimeexpected);

    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $videolecture
 * @return bool
 */
function videolecture_update_instance($videolecture) {
    global $DB;

    $videolecture->timemodified = time();
    $videolecture->id = $videolecture->instance;

    $context = context_module::instance($videolecture->coursemodule);

    $attachmentoptions =  array('subdirs' => VL_FILEMANAGER_SUBDIRS, 'maxbytes' => VL_FILEMANAGER_MAXBYTES, 'areamaxbytes' => VL_FILEMANAGER_AREAMAXBYTES,
        'maxfiles' => VL_FILEMANAGER_MAXFILES, 'accepted_types' => VL_FILEMANAGER_ACCEPTED_TYPES, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL);

    $videolecture = file_postupdate_standard_filemanager($videolecture, 'attachment', $attachmentoptions, $context, 'mod_videolecture',
        'attachment', $videolecture->id);

    $completiontimeexpected = !empty($videolecture->completionexpected) ? $videolecture->completionexpected : null;
    \core_completion\api::update_completion_date_event($videolecture->coursemodule, 'videolecture', $videolecture->id, $completiontimeexpected);

    return $DB->update_record("videolecture", $videolecture);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function videolecture_delete_instance($id) {
    global $DB;

    if (!$videolecture = $DB->get_record("videolecture", array("id" => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('videolecture', $id)) {
        return false;
    }

    if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
        return false;
    }

    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    \core_completion\api::update_completion_date_event($cm->id, 'videolecture', $videolecture->id, null);

    $DB->delete_records("videolecture", array("id" => $videolecture->id));

    return true;
}

/**
 * Serves the videolectures attachments. Implements needed access control
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function videolecture_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'attachment' || $filearea === 'intro')
    {
        if (!has_capability('mod/videolecture:view', $context)) {
            return false;
        }

        $videolectureid = (int)array_shift($args);

        if ($filearea === 'attachment') {
            if (!$videolecture = $DB->get_record('videolecture', array('id' => $videolectureid))) {
                return false;
            }
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);

        if ($filearea === 'attachment') {
            $fullpath = "/$context->id/mod_videolecture/attachment/$videolectureid/$relativepath";
        }

        if ($filearea === 'intro') {
            $fullpath = "/$context->id/mod_videolecture/intro/$relativepath";
        }

        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // Finally send the file.
        send_stored_file($file, 0, 0, true, $options);
    }
    else return false;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function videolecture_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-videolecture-*' => get_string('page-mod-videolecture-x', 'page'));
    return $module_pagetype;
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function videolecture_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('attachment'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_videolecture_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    int $userid = 0
){
    global $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['videolecture'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/videolecture/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
