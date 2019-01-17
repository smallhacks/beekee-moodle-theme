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
 * A two column layout for the Beekee theme.
 *
 * @package    theme_beekee
 * @copyright  2019 Vincent Widmer, based on Boost and Moov themes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

    $navdraweropen = false;

// BEEKEE : Hide drawer on front page
// if (isloggedin()) {
//     $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
// } else {
//     $navdraweropen = false;
// }
// $extraclasses = [];
// if ($navdraweropen) {
//     $extraclasses[] = 'drawer-open-left';
// }

// BEEKEE : add extra body class depending on capability
$context = get_context_instance(CONTEXT_COURSE,$COURSE->id);
if (has_capability('moodle/course:create', context_system::instance())) {
    $extraclasses[] = "editor";
} else {
    $extraclasses[] = "noneditor";
}
if (has_capability('moodle/site:config', context_system::instance())) {
    $extraclasses[] = "admin";
} else {
    $extraclasses[] = "nonadmin";
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$centertopblockshtml = $OUTPUT->blocks('center-top');
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'centertopblocks' => $centertopblockshtml,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu)
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;
echo $OUTPUT->render_from_template('theme_beekee/frontpage', $templatecontext);

