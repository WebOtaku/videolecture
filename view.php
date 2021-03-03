<?php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'videolecture');
$videolecture = $DB->get_record('videolecture', array('id'=> $cm->instance), '*', MUST_EXIST);

$PAGE->set_url('/mod/videolecture/view.php', array('id' => $id));
require_course_login($course->id, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/videolecture:view', $context);

$PAGE->set_title($videolecture->name);
$PAGE->set_heading($videolecture->name);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
echo '<link rel="stylesheet" href="main.css">';
echo '<div class="videolecture">';
echo $OUTPUT->heading(format_string($videolecture->name), 2);
echo vl_video($context, $videolecture);
echo vl_desc($context, $videolecture);
echo '</div>';
echo $OUTPUT->footer();

function vl_video($context, $videolecture)
{
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_videolecture', 'attachment', false, '', false);
    $file = reset($files);

    if ($file) {
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );

        $vlidstr = 'videolecture'. $videolecture->id;

        $html_str = '
            <div class="videolecture__video">
               <script type="text/javascript">
                    document.onreadystatechange = function()  {
                        if (document.readyState == "interactive") {
                            document.oncontextmenu = function(e) {
                                e.preventDefault();
                                e.stopPropagation(); 
                                e.stopImmediatePropagation();
                                return false;
                            }
                        }
                    }
                </script>
                <video id="'. $vlidstr .'" class="video" controls="controls" controlslist="nodownload"> 
                    <source src="' . $url . '" /> 
                </video>
            </div>
        ';
    }
    else $html_str = '
        <div class="videolecture__video">
            <h3>' . get_string('videomissing', 'videolecture') . '</h3>
        </div>
    ';

    return $html_str;
}

function vl_desc($context, $videolecture) {
    $description = file_rewrite_pluginfile_urls($videolecture->intro, 'pluginfile.php',
        $context->id, 'mod_videolecture', 'intro', '');
    $html_str = ' <div class="videolecture__description">'. $description .'</div>';
    return $html_str;
}