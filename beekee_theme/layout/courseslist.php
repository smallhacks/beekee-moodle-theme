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
 * A drawer based layout for the boost theme.
 *
 * @package   theme_boost
 * @copyright 2021 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once( $CFG->libdir . '/filelib.php' );

// Beekee
include_once($CFG->dirroot . '/course/lib.php');
//include_once($CFG->dirroot . '/course/enrol.php');

// require_once($CFG->libdir . '/completionlib.php');
// use core_completion\progress;

// global $COURSE;

// $course = $COURSE;


global $USER,$DB;



// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

$context = context_system::instance();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
//var_dump($header);
//$PAGE->activityheader->$_heading('Your New Title');

// $header->title = "BE1";
// $header->shortname = "BE2";

$headercontent = $header->export_for_template($renderer);

// Beekee
global $DB;




// BEEKEE @ 13/11/2023 Vincent Widmer
// Get all categories and all courses
//$enrolled_courses_category_ids = array();
//$enrolled_courses_ids = array();

$sort_order = array();
$sort_order = ['sortorder'];


//$categories = $DB->get_records('course_categories', array(), 'id ASC');
$categories = $DB->get_records('course_categories', array('visible' => 1), 'id ASC');


foreach ($categories as $category) {
    $data_category = array();
    $data_category['id'] = $category->id;
    $data_category['name'] = $category->name;
    $data_category['description'] = $category->description;

    // Retrieve the courses in this category
    $courses_list = array();

    //$courses_in_category = $DB->get_records('course', array('category' => $category->id), 'sortorder ASC');

    $courses_in_category = $DB->get_records_select('course', "category = :category AND visible = 1", array('category' => $category->id), 'sortorder ASC');

    foreach ($courses_in_category as $course) {
            $data_course = array();
            $data_course['fullname'] = $course->fullname;
            $data_course['summary'] = $course->summary;
            $data_course['url'] = new moodle_url('/course/view.php?id=', ['id' => $course->id]);

            /* Retrieve course custom fields */
            $handler = \core_customfield\handler::get_handler('core_course', 'course');

            $datas = $handler->get_instance_data($course->id);
            $metadata = [];
            foreach ($datas as $data) {
                if (empty($data->get_value())) {
                    continue;
                }
                $cat = $data->get_field()->get_category()->get('name');
                $metadata[$data->get_field()->get('shortname')] = $cat . ': ' . $data->get_value();
            }

            $context = context_course::instance($course->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0 );
            $data_course['imageurl'] = '';
            if (!empty($files)) {
                foreach ($files as $file) {
                    if ($file->is_valid_image()) {
                        $data_course['imageurl'] = moodle_url::make_pluginfile_url( $file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename(), false );
                        break;
                    }
                }  
            } else {
                // No image found, return a random pattern image
                $data_course['imageurl'] = get_generated_image_for_id($course->id);
            }

            $courses_list[] = $data_course;

        }
            $data_category['courses_list'] = $courses_list;
    $categories_list[] = $data_category;
}

// Based on the Moodle core function
function get_generated_image_for_id($id) {
    $color = get_generated_color_for_id($id);
    $pattern = new \core_geopattern();
    $pattern->setColor($color);
    $pattern->patternbyid($id);
    return $pattern->datauri();
}

// Based on the Moodle core function
function get_generated_color_for_id($id) {
        $colornumbers = range(1, 10);
        $basecolors = [];
        foreach ($colornumbers as $number) {
            $basecolors[] = get_config('core_admin', 'coursecolor' . $number);
        }
        $color = $basecolors[$id % 10];
        return $color;
}
//$courses_enrolled = enrol_get_users_courses($USER->id, true);

// For each courses, get the id of each courses enrolled in and the categories id and store them into arrays
// foreach ($courses_enrolled as $course) {
//     array_push($enrolled_courses_category_ids, $course->category);
//     array_push($enrolled_courses_ids, $course->id);
// }

// // Convert a PHP array into a readable string for SQL
// $enrolled_courses_category_ids_string = implode ( ', ', $enrolled_courses_category_ids);

// $categories_list = array();

// // Get the categories which contains courses where user is enrolled in
// if ($enrolled_courses_category_ids_string) {
// $category_query = "SELECT cat.id, cat.name, cat.description, cat.sortorder from {course_categories} as cat WHERE cat.id IN ($enrolled_courses_category_ids_string) ORDER BY cat.sortorder";
// $categories = $DB->get_records_sql($category_query);
// }

// foreach ($categories as $category) {
//     $data_category = array();
//     $data_category['id'] = $category->id;
//     $data_category['name'] = $category->name;
//     $data_category['description'] = $category->description;

//     // Retrieve the courses in this category
//     $courses_list = array();

//     $course_query = "SELECT id, fullname, summary, sortorder from {course}
//     WHERE category = $category->id ORDER BY sortorder";
//     $courses_in_category = $DB->get_records_sql($course_query);


//     foreach ($courses_in_category as $course) {

//         // Check if the user is enrolled in the course
//         if (in_array($course->id, $enrolled_courses_ids)) {

//             $data_course = array();
//             $data_course['fullname'] = $course->fullname;
//             $data_course['summary'] = $course->summary;
//             $data_course['url'] = new moodle_url('/course/view.php?id=', ['id' => $course->id]);

//             /* Retrieve course custom fields */
//             $handler = \core_customfield\handler::get_handler('core_course', 'course');

//             $datas = $handler->get_instance_data($course->id);
//             $metadata = [];
//             foreach ($datas as $data) {
//                 if (empty($data->get_value())) {
//                     continue;
//                 }
//                 $cat = $data->get_field()->get_category()->get('name');
//                 $metadata[$data->get_field()->get('shortname')] = $cat . ': ' . $data->get_value();
//             }

//             /* Retrieve the course image */
//             // $context = context_course::instance($course->id);
//             // $fs = get_file_storage();
//             // $files = $fs->get_area_files($context->id, 'course', 'summary', false, 'sortorder', false);
//             // $data_course['imageurl'] = '';
//             // foreach ($files as $file) {
//             //     if ($file->is_valid_image()) {
//             //         $data_course['imageurl'] = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
//             //         break;
//             //     }
//             // }   



//             $context = context_course::instance($course->id);
//             $fs = get_file_storage();
//             $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0 );
//             $data_course['imageurl'] = '';
//             foreach ($files as $file) {
//                 if ($file->is_valid_image()) {
//                     $data_course['imageurl'] = moodle_url::make_pluginfile_url( $file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename(), false );
//                     break;
//                 }
//             }   


//     // function get_course_image()
//     // {
//     //    global $COURSE;
//     //    $url = '';
//     //    require_once( $CFG->libdir . '/filelib.php' );

//     //    $context = context_course::instance( $COURSE->id );
//     //    $fs = get_file_storage();
//     //    $files = $fs->get_area_files( $context->id, 'course', 'overviewfiles', 0 );

//     //    foreach ( $files as $f )
//     //    {
//     //      if ( $f->is_valid_image() )
//     //      {
//     //         $url = moodle_url::make_pluginfile_url( $f->get_contextid(), $f->get_component(), $f->get_filearea(), null, $f->get_filepath(), $f->get_filename(), false );
//     //      }
//     //    }

//     //    return $url;
//     // }






//             //var_dump("HEYY");
//             //var_dump($data_course['imageurl']);

//             $course_progress_percentage = round(\core_completion\progress::get_course_progress_percentage($course, $USER->id));
//             $data_course['progress_percentage'] = $course_progress_percentage;
//             if ($course_progress_percentage == 0) {
//                 $is_course_started = false;
//                 $is_course_completed = false;
//             } 
//             else if ($course_progress_percentage == 100) {
//                 $is_course_started = true;
//                 $is_course_completed = true;

//             }
//             else {
//                 $is_course_started = true;
//                 $is_course_completed = false;
//             }



//             $data_course['is_course_started'] = $is_course_started;
//             $data_course['is_course_completed'] = $is_course_completed;

//             //$data_course['progress_pecentage'] = get_course_progress_percentage($course, 2);
//             $courses_list[] = $data_course;
//             }
//         }
//     $data_category['courses_list'] = $courses_list;
//     $categories_list[] = $data_category;
// }

//BEEKEE
$compact_logo_url = $this->get_compact_logo_url();
$PAGE->set_title(get_string('courseslist_page_title', 'theme_beekee_theme'));


$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    // BEEKEE
    'compact_logo_url' => $compact_logo_url,
    'categories_list' => array_values($categories_list),
    'home_url' => new moodle_url('/')
];

// BEEKEE - Check if Site Administration link should be shown
if (has_capability('moodle/site:configview', $context, $USER->id)) {
        $templatecontext['is_admin'] = true;
}


echo $OUTPUT->render_from_template('theme_beekee_theme/courseslist', $templatecontext);
