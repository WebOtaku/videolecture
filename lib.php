<?php

defined('MOODLE_INTERNAL') || die;

define('VL_FILEMANAGER_MAXBYTES', 1073741824, true);
define('VL_FILEMANAGER_MAXFILES', 1, true);
define('VL_FILEMANAGER_AREAMAXBYTES', VL_FILEMANAGER_MAXBYTES * VL_FILEMANAGER_MAXFILES, true);
define('VL_FILEMANAGER_SUBDIRS', false, true);
define('VL_FILEMANAGER_ACCEPTED_TYPES', array('video'), true);

require_once(__DIR__ . '/../../config.php');

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
