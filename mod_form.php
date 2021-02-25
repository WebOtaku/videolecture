<?php

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/videolecture/lib.php');

class mod_videolecture_mod_form extends moodleform_mod
{
    function definition() {
        global $PAGE;

        $PAGE->force_settings_menu();

        $mform = $this->_form;

        $this->_features->showdescription = 1;

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('text', 'name', get_string('name', 'videolecture'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $attachmentoptions =  array('subdirs' => VL_FILEMANAGER_SUBDIRS, 'maxbytes' => VL_FILEMANAGER_MAXBYTES, 'areamaxbytes' => VL_FILEMANAGER_AREAMAXBYTES,
            'maxfiles' => VL_FILEMANAGER_MAXFILES, 'accepted_types' => VL_FILEMANAGER_ACCEPTED_TYPES, 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL);;

        if (!$this->current->id) {
            $entry = new stdClass();
            $entry->id = null;
        } else {
            $entry = $this->current;
            $context = context_module::instance($this->_cm->id);
            $entry = file_prepare_standard_filemanager($entry, 'attachment', $attachmentoptions, $context, 'mod_videolecture', 'attachment', $this->current->id);
        }

        $mform->addElement('filemanager', 'attachment_filemanager', get_string('video', 'videolecture'), null, $attachmentoptions);
        $mform->addRule('attachment_filemanager', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);

        $this->set_data($entry);
    }

}

