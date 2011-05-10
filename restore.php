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
 * Script that actually restores backup.
 *
 * @copyright &copy; 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local
 * @subpackage broom
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

$fileid = required_param('file', PARAM_INT);
$pageparams = array('file'=>$fileid);

$context = get_context_instance(CONTEXT_SYSTEM);

$pluginname = get_string('pluginname', 'local_broom');
$pagename = get_string('restore');

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

print $OUTPUT->header();

print html_writer::tag('h2', get_string('restore'));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_broom', 'backupfiles', 0, 'sortorder', false);
/** @var stored_file */
$found = null;
foreach ($files as $file) {
    if ($file->get_id() == $fileid) {
        $found = $file;
    }
}
if (!$found) {
    print_error('error', 'local_broom');
}

// Unzip backup
$rand = $USER->id;
while (strlen($rand) < 10) {
    $rand = '0' . $rand;
}
$rand .= rand();
check_dir_exists($CFG->dataroot . '/temp/backup');
$found->extract_to_pathname(get_file_packer(), $CFG->dataroot . '/temp/backup/' . $rand);

// Get or create category
$categoryname = 'Broom restores';
$categoryid = $DB->get_field('course_categories', 'id', array('name'=>$categoryname));
if (!$categoryid) {
    $categoryid = $DB->insert_record('course_categories', (object)array(
        'name' => $categoryname,
        'parent' => 0,
        'visible' => 0
    ));
    $DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
}

$shortname = 'BRM' . date('His');
$fullname = 'Broom restore ' . date('Y-m-d H:i:s');

// Create new course
$courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);

// Restore backup into course
$controller = new restore_controller($rand, $courseid,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id,
        backup::TARGET_NEW_COURSE);
$controller->get_logger()->set_next(new output_indented_logger(backup::LOG_INFO, false, true));
$controller->execute_precheck();
$controller->execute_plan();

// Set shortname and fullname back!
$DB->update_record('course', (object)array(
    'id' => $courseid,
    'shortname' => $shortname,
    'fullname' => $fullname
));

print html_writer::tag('p', get_string('restoredone', 'local_broom'));

$courseurl = new moodle_url('/course/view.php', array('id'=>$courseid));
print html_writer::tag('p', html_writer::tag('a',
        get_string('viewcourse', 'local_broom'),
        array('href'=>$courseurl->out(), 'target'=>'_blank')));

$hash = md5($DB->get_field('course', 'timemodified', array('id'=>$courseid)));
$deleteurl = new moodle_url('/course/delete.php');
print html_writer::tag('form', html_writer::tag('input', '',
        array('type'=>'submit', 'value'=>get_string('deletecourse', 'local_broom'), 'id'=>'d',
            'onclick'=>'document.getElementById("r").disabled=false; ' .
                'document.getElementById("d").disabled=true; return true;')) .
        html_writer::tag('input', '', array('type'=>'hidden', 'name'=>'id', 'value'=>$courseid)) .
        html_writer::tag('input', '', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey())) .
        html_writer::tag('input', '', array('type'=>'hidden', 'name'=>'delete', 'value'=>$hash)),
        array('action'=>$deleteurl->out(), 'method'=>'post', 'target'=>'_blank'));

print html_writer::tag('form', html_writer::tag('input', '',
        array('type'=>'submit', 'value'=>get_string('restoreagain', 'local_broom'),
            'id'=>'r')) .
        html_writer::tag('input', '', array('type'=>'hidden', 'name'=>'file',
            'value'=>$file->get_id())),
        array('action'=>'restore.php', 'method'=>'post'));

print html_writer::tag('script', 'document.getElementById("r").disabled=true;',
        array('type'=>'text/javascript'));

print $OUTPUT->footer();

