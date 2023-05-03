<?php
// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();


function theme_beekee_theme_get_main_scss_content($theme) {
    global $CFG;
 
    $scss = file_get_contents($CFG->dirroot . '/theme/beekee_theme/scss/style.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/fontawesome.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/bootstrap.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/moodle.scss');
    return $scss;
}