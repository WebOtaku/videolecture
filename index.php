<?php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id'=> $id))) {
    print_error('invalidcourseid');
}

$PAGE->set_url('/mod/videolecture/index.php', array('id' => $id));
require_course_login($course);

$context = context_course::instance($course->id);

/// Get all required strings
$strlectures = get_string('modulenameplural', 'videolecture');
$strlecture  = get_string('modulename', 'videolecture');

$PAGE->set_pagelayout('standard');

/// Print the header
$PAGE->navbar->add($strlectures, "index.php?id=$course->id");
$PAGE->set_title($strlectures);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($strlectures), 2);

/// Get all the appropriate data
if (!$videolectures = get_all_instances_in_course('videolecture', $course)) {
    notice(get_string('thereareno', 'moodle', $strlectures), "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)
$strname = get_string('name', 'videolecture');
$strdesc = get_string('description', 'videolecture');
$strcompleted = get_string('completed', 'videolecture');
$stryes = get_string('yes', 'videolecture');
$strno = get_string('no', 'videolecture');
$strunknown = get_string('unknown', 'videolecture');

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    //$table->head  = array ($strsectionname, $strname, $strdesc);
    $table->head  = array ($strsectionname, $strname, $strcompleted);
    $table->align = array ('center', 'left', 'center');
} else {
    //$table->head  = array ($strname, $strdesc);
    $table->head  = array ($strname, $strcompleted);
    $table->align = array ('left', 'center');
}

$currentsection = "";

foreach ($videolectures as $videolecture)
{
    //$cmcontext = context_module::instance($videolecture->coursemodule);

    if (!$videolecture->visible && has_capability('moodle/course:viewhiddenactivities', $context)) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$videolecture->coursemodule\">".format_string($videolecture->name, true)."</a>";
    } else if ($videolecture->visible) {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$videolecture->coursemodule\">".format_string($videolecture->name, true)."</a>";
    } else {
        // Don't show.
        continue;
    }

    $printsection = "";
    if ($usesections) {
        if ($videolecture->section !== $currentsection) {
            if ($videolecture->section) {
                $printsection = get_section_name($course, $videolecture->section);
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $videolecture->section;
        }
    }

    $cmcompletition = $DB->get_record('course_modules_completion', array(
        'coursemoduleid' => $videolecture->coursemodule,
        'userid' => $USER->id
    ));

    if (isset($cmcompletition->completionstate))
        $cmcompletition = ($cmcompletition->completionstate)? $stryes : $strno;
    else
        $cmcompletition = $strunknown;

    /*$description = file_rewrite_pluginfile_urls($videolecture->intro, 'pluginfile.php',
        $cmcontext->id, 'mod_videolecture', 'intro', '');*/

    if ($usesections) {
        //$linedata = array ($printsection, $link, $description);
        $linedata = array ($printsection, $link, $cmcompletition);
    } else {
        //$linedata = array ($link, $description);
        $linedata = array ($link, $cmcompletition);
    }

    $table->data[] = $linedata;
}

echo "<br/>";

echo html_writer::table($table);

/// Finish the pag
echo $OUTPUT->footer();
