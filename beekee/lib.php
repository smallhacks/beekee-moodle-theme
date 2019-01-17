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
 * Theme functions.
 *
 * @package    theme_beekee
 * @copyright  2019 Vincent Widmer, based on Boost and Moov themes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();



/**
 * Get a list of essential user navigation items.
 *
 * @param stdclass $user user object.
 * @param moodle_page $page page object.
 * @param array $options associative array.
 *     options are:
 *     - avatarsize=35 (size of avatar image)
 * @return stdClass $returnobj navigation information object, where:
 *
 *      $returnobj->navitems    array    array of links where each link is a
 *                                       stdClass with fields url, title, and
 *                                       pix
 *      $returnobj->metadata    array    array of useful user metadata to be
 *                                       used when constructing navigation;
 *                                       fields include:
 *
 *          ROLE FIELDS
 *          asotherrole    bool    whether viewing as another role
 *          rolename       string  name of the role
 *
 *          USER FIELDS
 *          These fields are for the currently-logged in user, or for
 *          the user that the real user is currently logged in as.
 *
 *          userid         int        the id of the user in question
 *          userfullname   string     the user's full name
 *          userprofileurl moodle_url the url of the user's profile
 *          useravatar     string     a HTML fragment - the rendered
 *                                    user_picture for this user
 *          userloginfail  string     an error string denoting the number
 *                                    of login failures since last login
 *
 *          "REAL USER" FIELDS
 *          These fields are for when asotheruser is true, and
 *          correspond to the underlying "real user".
 *
 *          asotheruser        bool    whether viewing as another user
 *          realuserid         int        the id of the user in question
 *          realuserfullname   string     the user's full name
 *          realuserprofileurl moodle_url the url of the user's profile
 *          realuseravatar     string     a HTML fragment - the rendered
 *                                        user_picture for this user
 *
 *          MNET PROVIDER FIELDS
 *          asmnetuser            bool   whether viewing as a user from an
 *                                       MNet provider
 *          mnetidprovidername    string name of the MNet provider
 *          mnetidproviderwwwroot string URL of the MNet provider
 */
function theme_beekee_user_get_user_navigation_info($user, $page, $options = array()) {
    global $OUTPUT, $DB, $SESSION, $CFG;

    $returnobject = new stdClass();
    $returnobject->navitems = array();
    $returnobject->metadata = array();

    $course = $page->course;

    // Query the environment.
    $context = context_course::instance($course->id);

    // Get basic user metadata.
    $returnobject->metadata['userid'] = $user->id;
    $returnobject->metadata['userfullname'] = fullname($user, true);
    $returnobject->metadata['userprofileurl'] = new moodle_url('/user/profile.php', array(
        'id' => $user->id
    ));

    $avataroptions = array('link' => false, 'visibletoscreenreaders' => false);
    if (!empty($options['avatarsize'])) {
        $avataroptions['size'] = $options['avatarsize'];
    }
    $returnobject->metadata['useravatar'] = $OUTPUT->user_picture (
        $user, $avataroptions
    );
    // Build a list of items for a regular user.

    // Query MNet status.
    if ($returnobject->metadata['asmnetuser'] = is_mnet_remote_user($user)) {
        $mnetidprovider = $DB->get_record('mnet_host', array('id' => $user->mnethostid));
        $returnobject->metadata['mnetidprovidername'] = $mnetidprovider->name;
        $returnobject->metadata['mnetidproviderwwwroot'] = $mnetidprovider->wwwroot;
    }

    // Did the user just log in?
    if (isset($SESSION->justloggedin)) {
        // Don't unset this flag as login_info still needs it.
        if (!empty($CFG->displayloginfailures)) {
            // Don't reset the count either, as login_info() still needs it too.
            if ($count = user_count_login_failures($user, false)) {

                // Get login failures string.
                $a = new stdClass();
                $a->attempts = html_writer::tag('span', $count, array('class' => 'value'));
                $returnobject->metadata['userloginfail'] =
                    get_string('failedloginattempts', '', $a);

            }
        }
    }

    // Links: Dashboard.
    // $myhome = new stdClass();
    // $myhome->itemtype = 'link';
    // $myhome->url = new moodle_url('/my/');
    // $myhome->title = get_string('mymoodle', 'admin');
    // $myhome->titleidentifier = 'mymoodle,admin';
    // $myhome->pix = "i/dashboard";
    // $returnobject->navitems[] = $myhome;

    // Links: My Profile.
    // $myprofile = new stdClass();
    // $myprofile->itemtype = 'link';
    // $myprofile->url = new moodle_url('/user/profile.php', array('id' => $user->id));
    // $myprofile->title = get_string('profile');
    // $myprofile->titleidentifier = 'profile,moodle';
    // $myprofile->pix = "i/user";
    // $returnobject->navitems[] = $myprofile;

    // $returnobject->metadata['asotherrole'] = false;

    // Before we add the last items (usually a logout + switch role link), add any
    // custom-defined items.
    $customitems = user_convert_text_to_menu_items($CFG->customusermenuitems, $page);
    foreach ($customitems as $item) {
        $returnobject->navitems[] = $item;
    }


    if ($returnobject->metadata['asotheruser'] = \core\session\manager::is_loggedinas()) {
        $realuser = \core\session\manager::get_realuser();

        // Save values for the real user, as $user will be full of data for the
        // user the user is disguised as.
        $returnobject->metadata['realuserid'] = $realuser->id;
        $returnobject->metadata['realuserfullname'] = fullname($realuser, true);
        $returnobject->metadata['realuserprofileurl'] = new moodle_url('/user/profile.php', array(
            'id' => $realuser->id
        ));
        $returnobject->metadata['realuseravatar'] = $OUTPUT->user_picture($realuser, $avataroptions);

        // Build a user-revert link.
        $userrevert = new stdClass();
        $userrevert->itemtype = 'link';
        $userrevert->url = new moodle_url('/course/loginas.php', array(
            'id' => $course->id,
            'sesskey' => sesskey()
        ));
        $userrevert->pix = "a/logout";
        $userrevert->title = get_string('logout');
        $userrevert->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $userrevert;

    } else {

        // Build a logout link.
        $logout = new stdClass();
        $logout->itemtype = 'link';
        $logout->url = new moodle_url('/login/logout.php', array('sesskey' => sesskey()));
        $logout->pix = "a/logout";
        $logout->title = get_string('logout');
        $logout->titleidentifier = 'logout,moodle';
        $returnobject->navitems[] = $logout;
    }

    if (is_role_switched($course->id)) {
        if ($role = $DB->get_record('role', array('id' => $user->access['rsw'][$context->path]))) {
            // Build role-return link instead of logout link.
            $rolereturn = new stdClass();
            $rolereturn->itemtype = 'link';
            $rolereturn->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'sesskey' => sesskey(),
                'switchrole' => 0,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $rolereturn->pix = "a/logout";
            $rolereturn->title = get_string('switchrolereturn');
            $rolereturn->titleidentifier = 'switchrolereturn,moodle';
            $returnobject->navitems[] = $rolereturn;

            $returnobject->metadata['asotherrole'] = true;
            $returnobject->metadata['rolename'] = role_get_name($role, $context);

        }
    } else {
        // Build switch role link.
        $roles = get_switchable_roles($context);
        if (is_array($roles) && (count($roles) > 0)) {
            $switchrole = new stdClass();
            $switchrole->itemtype = 'link';
            $switchrole->url = new moodle_url('/course/switchrole.php', array(
                'id' => $course->id,
                'switchrole' => -1,
                'returnurl' => $page->url->out_as_local_url(false)
            ));
            $switchrole->pix = "i/switchrole";
            $switchrole->title = get_string('switchroleto');
            $switchrole->titleidentifier = 'switchroleto,moodle';
            $returnobject->navitems[] = $switchrole;
        }
    }

    return $returnobject;
}