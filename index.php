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
 * Main Broom page.
 *
 * @copyright &copy; 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local
 * @subpackage broom
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$pageparams = array();

$context = get_context_instance(CONTEXT_SYSTEM);

$pluginname = get_string('pluginname', 'local_broom');

$PAGE->set_url(new moodle_url('/local/broom/', $pageparams));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname. ': ' . $pluginname);

$PAGE->navbar->add($pluginname);

require_login();
require_capability('moodle/site:config', $context);
if (!debugging('', DEBUG_DEVELOPER)) {
    print_error('error',  'local_broom');
}

print $OUTPUT->header();

print html_writer::tag('h2', get_string('pluginname', 'local_broom'));
print html_writer::tag('p', get_string('acronym', 'local_broom'));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_broom', 'backupfiles', 0, 'sortorder', false);

print html_writer::tag('div', html_writer::tag('a', get_string('list', 'local_broom'),
    array('href'=>'list.php')));

if (count($files) > 0) {
    print html_writer::start_tag('ul');
    foreach ($files as $file) {
        $content = html_writer::tag('div', s($file->get_filename()),
            array('class'=>'local_broom_filename')) .
            html_writer::tag('form', html_writer::tag('input', '',
                array('type'=>'submit', 'value'=>get_string('restore'))) .
                html_writer::tag('input', '', array('type'=>'hidden', 'name'=>'file',
                    'value'=>$file->get_id())),
                array('action'=>'restore.php', 'method'=>'post'),
                array('class'=>'local_broom_link'));
        print html_writer::tag('li', $content);
    }
    print html_writer::end_tag('ul');
}

print $OUTPUT->footer();
