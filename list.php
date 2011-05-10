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
 * Form for editing list of files within the Broom system.
 *
 * @copyright &copy; 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local
 * @subpackage broom
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$pageparams = array();
$fileoptions = array('subdirs'=>false, 'maxbytes'=>$CFG->maxbytes, 'accepted_types' => array('*.mbz'));

/**
 * Form for managing list of stored backups.
 */
class local_broom_list_form extends moodleform {
    public function definition() {
        global $CFG, $fileoptions;
        $mform = $this->_form;

        // File manager
        $mform->addElement('filemanager', 'backupfiles',
                get_string('backupfiles', 'local_broom'), null, $fileoptions);
        $mform->addElement('submit', 'savechanges',
                get_string('savechanges'));
    }
}

$context = get_context_instance(CONTEXT_SYSTEM);

$pluginname = get_string('pluginname', 'local_broom');
$pagename = get_string('list', 'local_broom');

$PAGE->set_url(new moodle_url('/local/createwebsite/', $pageparams));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname. ': ' . $pagename);

$PAGE->navbar->add($pluginname, new moodle_url('/local/broom/'));
$PAGE->navbar->add($pagename);

require_login();
require_capability('moodle/site:config', $context);
if (!debugging('', DEBUG_DEVELOPER)) {
    print_error('error',  'local_broom');
}

$mform = new local_broom_list_form('list.php');
if ($data = $mform->get_data()) {
    file_save_draft_area_files($data->backupfiles, $context->id, 'local_broom',
            'backupfiles', 0, $fileoptions);
    redirect(new moodle_url('./'));
}

print $OUTPUT->header();

print html_writer::tag('h2', get_string('list', 'local_broom'));

$draftitemid = file_get_submitted_draft_itemid('backupfiles');
file_prepare_draft_area($draftitemid, $context->id, 'local_broom',
        'backupfiles', 0, $fileoptions);
$initialvalues = (object)array(
    'backupfiles' => $draftitemid);

$mform->set_data($initialvalues);
$mform->display();

print $OUTPUT->footer();

