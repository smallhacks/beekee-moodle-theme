<?php

//defined('MOODLE_INTERNAL') || die();

//defined('MOODLE_INTERNAL') || die();
//require_once($CFG->dirroot . '/course/lib.php');

//namespace theme_beekee_theme\output\mod_lesson;
//namespace theme_beekee_theme\output;
//namespace theme_beekee_theme\output;

//namespace theme_lac\output\core;


//namespace theme_iam\output\mod_quiz;
//defined('MOODLE_INTERNAL') || die;
//class theme_iam_mod_quiz_renderer extends \mod_quiz_renderer {

class theme_beekee_theme_mod_lesson_renderer extends \mod_lesson_renderer {


    /**
     * Returns the HTML for displaying the end of lesson page.
     *
     * @param  lesson $lesson lesson instance
     * @param  stdclass $data lesson data to be rendered
     * @return string         HTML contents
     */
    public function display_eol_page(lesson $lesson, $data) {

        $output = '';
        $canmanage = $lesson->can_manage();
        $course = $lesson->courserecord;

        if ($lesson->custom && !$canmanage && (($data->gradeinfo->nquestions < $lesson->minquestions))) {
            $output .= $this->box_start('generalbox boxaligncenter');
        }

        if ($data->gradelesson) {
            // We are using level 3 header because the page title is a sub-heading of lesson title (MDL-30911).
            $output .= $this->heading(get_string("congratulations", "lesson"), 3);
            $output .= $this->box_start('generalbox boxaligncenter');
        }

        if ($data->notenoughtimespent !== false) {
            $output .= $this->paragraph(get_string("notenoughtimespent", "lesson", $data->notenoughtimespent), 'center');
        }

        if ($data->numberofpagesviewed !== false) {
            $output .= $this->paragraph(get_string("numberofpagesviewed", "lesson", $data->numberofpagesviewed), 'center');
        }
        if ($data->youshouldview !== false) {
            $output .= $this->paragraph(get_string("youshouldview", "lesson", $data->youshouldview), 'center');
        }
        if ($data->numberofcorrectanswers !== false) {
            $output .= $this->paragraph(get_string("numberofcorrectanswers", "lesson", $data->numberofcorrectanswers), 'center');
        }

        if ($data->displayscorewithessays !== false) {
            $output .= $this->box(get_string("displayscorewithessays", "lesson", $data->displayscorewithessays), 'center');
        } else if ($data->displayscorewithoutessays !== false) {
            $output .= $this->box(get_string("displayscorewithoutessays", "lesson", $data->displayscorewithoutessays), 'center');
        }

        if ($data->yourcurrentgradeisoutof !== false) {
            $output .= $this->paragraph(get_string("yourcurrentgradeisoutof", "lesson", $data->yourcurrentgradeisoutof), 'center');
        }
        if ($data->yourcurrentgradeis !== false) {
            $output .= $this->paragraph(get_string("yourcurrentgradeis", "lesson", $data->yourcurrentgradeis), 'center');
        }
        if ($data->eolstudentoutoftimenoanswers !== false) {
            $output .= $this->paragraph(get_string("eolstudentoutoftimenoanswers", "lesson"));
        }
        if ($data->welldone !== false) {
            $output .= $this->paragraph(get_string("welldone", "lesson"));
        }

        if ($data->progresscompleted !== false) {
            $output .= $this->progress_bar($lesson, $data->progresscompleted);
        }

        if ($data->displayofgrade !== false) {
            $output .= $this->paragraph(get_string("displayofgrade", "lesson"), 'center');
        }

        // BEEKEE
       $output .= $this->paragraph('<div class="alert alert-success alert-block fade in "><i class="fa fa-check text-success"></i> '.get_string("lesson_congratulations_message", "theme_beekee_theme").'</div>', 'center');

        $output .= $this->box_end(); // End of Lesson button to Continue.

        if ($data->reviewlesson !== false) {
            $output .= html_writer::link($data->reviewlesson, get_string('reviewlesson', 'lesson'),
                array('class' => 'centerpadded lessonbutton XXX standardbutton pr-3'));
        }
        if ($data->modattemptsnoteacher !== false) {
            $output .= $this->paragraph(get_string("modattemptsnoteacher", "lesson"), 'centerpadded');
        }

        // BEEKEE
        if (has_capability('gradereport/user:view', context_course::instance($course->id))
                && $course->showgrades && $lesson->grade != 0 && !$lesson->practice) {
            $url = new moodle_url('/grade/index.php', array('id' => $course->id));
            $output .= html_writer::link($url, '<i class="fa fa-trophy"></i> '.get_string('viewgrades', 'lesson'),
                array('class' => 'centerpadded lessonbutton lessonbutton-showgrades standardbutton pr-3'));
        }
        $output .= html_writer::empty_tag(br);
        
        $output .= html_writer::start_tag(div, array('class' => 'text-center mt-5'));

        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $output .= html_writer::link($url, get_string('returnto', 'lesson', format_string($course->fullname, true)),
                array('class' => 'centerpadded lessonbutton standardbutton lessonbutton-return pr-3'));

        if ($data->activitylink !== false) {
            $output .= $data->activitylink;
        }

        $output .= html_writer::end_tag(div);

        return $output;
    }
}