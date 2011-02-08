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
 * Controls access to a HotPot activity
 *
 * @package   mod-hotpot
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class hotpot_access_manager {
    private $_hotpot;
    private $_timenow;
    private $_passwordrule = null;
    private $_securewindowrule = null;
    private $_safebrowserrule = null;
    private $_rules = array();

    /**
     * Create an instance for a particular quiz.
     * @param object $hotpot An instance of the class hotpot from locallib.php
     *      The HotPot activiti we will be controlling access to.
     * @param integer $timenow The time to use as 'now'.
     * @param boolean $canignoretimelimits Whether this user is exempt from time
     *      limits (has_capability('mod/quiz:ignoretimelimits', ...)).
     */
    public function __construct($hotpot, $timenow, $canignoretimelimits) {
        $this->_hotpot = $hotpot;
        $this->_timenow = $timenow;
        $this->create_standard_rules($canignoretimelimits);
    }

    /**
     * create_standard_rules
     *
     * @param xxx $canignoretimelimits
     */
    private function create_standard_rules($canignoretimelimits)  {
        $hotpot = $this->_hotpot;
        if ($hotpot->attemptlimit > 0) {
            $this->_rules[] = new num_attemptlimit_access_rule($this->_hotpot, $this->_timenow);
        }
        $this->_rules[] = new open_close_date_access_rule($this->_hotpot, $this->_timenow);
        if (!empty($hotpot->timelimit) && !$canignoretimelimits) {
            $this->_rules[] = new time_limit_access_rule($this->_hotpot, $this->_timenow);
        }
        if (!empty($hotpot->subnet)) {
            $this->_rules[] = new ipaddress_access_rule($this->_hotpot, $this->_timenow);
        }
        if (!empty($hotpot->password)) {
            $this->_passwordrule = new password_access_rule($this->_hotpot, $this->_timenow);
            $this->_rules[] = $this->_passwordrule;
        }
    }

    /**
     * accumulate_messages
     *
     * @param xxx $messages (passed by reference)
     * @param xxx $new
     */
    private function accumulate_messages(&$messages, $new)  {
        if (is_array($new)) {
            $messages = array_merge($messages, $new);
        } else if (is_string($new) && $new) {
            $messages[] = $new;
        }
    }

    /**
     * Print each message in an array, surrounded by &lt;p>, &lt;/p> tags.
     *
     * @param array $messages the array of message strings.
     * @param boolean $return if true, return a string, instead of outputting.
     *
     * @return mixed, if $return is true, return the string that would have been output, otherwise
     * return null.
     */
    public function print_messages($messages, $return=false) {
        $output = '';
        foreach ($messages as $message) {
            $output .= '<p>' . $message . "</p>\n";
        }
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Provide a description of the rules that apply to this quiz, such
     * as is shown at the top of the quiz view page. Note that not all
     * rules consider themselves important enough to output a description.
     *
     * @return array an array of description messages which may be empty. It
     *         would be sensible to output each one surrounded by &lt;p> tags.
     */
    public function describe_rules() {
        $result = array();
        foreach ($this->_rules as $rule) {
            $this->accumulate_messages($result, $rule->description());
        }
        return $result;
    }

    /**
     * Is it OK to let the current user start a new attempt now? If there are
     * any restrictions in force now, return an array of reasons why access
     * should be blocked. If access is OK, return false.
     *
     * @param integer $numattempts the number of previous attempts this user has made.
     * @param object|false $lastattempt information about the user's last completed attempt.
     *      if there is not a previous attempt, the false is passed.
     * @return mixed An array of reason why access is not allowed, or an empty array
     *         (== false) if access should be allowed.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        $reasons = array();
        foreach ($this->_rules as $rule) {
            $this->accumulate_messages($reasons,
                    $rule->prevent_new_attempt($numprevattempts, $lastattempt));
        }
        return $reasons;
    }

    /**
     * Is it OK to let the current user start a new attempt now? If there are
     * any restrictions in force now, return an array of reasons why access
     * should be blocked. If access is OK, return false.
     *
     * @return mixed An array of reason why access is not allowed, or an empty array
     *         (== false) if access should be allowed.
     */
    public function prevent_access() {
        $reasons = array();
        foreach ($this->_rules as $rule) {
            $this->accumulate_messages($reasons, $rule->prevent_access());
        }
        return $reasons;
    }

    /**
     * Do any of the rules mean that this student will no be allowed any further attempts at this
     * quiz. Used, for example, to change the label by the  grade displayed on the view page from
     * 'your current score is' to 'your final score is'.
     *
     * @param integer $numattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return boolean true if there is no way the user will ever be allowed to attempt this quiz again.
     */
    public function is_finished($numprevattempts, $lastattempt) {
        foreach ($this->_rules as $rule) {
            if ($rule->is_finished($numprevattempts, $lastattempt)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Print a button to start a quiz attempt, with an appropriate javascript warning,
     * depending on the access restrictions. The link will pop up a 'secure' window, if
     * necessary.
     *
     * @param boolean $canpreview whether this user can preview. This affects whether they must
     * use a secure window.
     * @param string $buttontext the label to put on the button.
     * @param boolean $unfinished whether the button is to continue an existing attempt,
     * or start a new one. This affects whether a javascript alert is shown.
     */
    public function print_start_attempt_button($canpreview, $buttontext, $unfinished) {
        global $OUTPUT;

        $url = $this->_hotpot->attempt_url();
        $button = new single_button($url, $buttontext);
        $button->class .= ' quizstartbuttondiv';

        if (!$unfinished) {
            $strconfirmstartattempt = $this->confirm_start_attempt_message();
            if ($strconfirmstartattempt) {
                $button->add_confirm_action($strconfirmstartattempt);
            }
        }

        $warning = '';

        echo $OUTPUT->render($button) . $warning;
    }

    /**
     * Send the user back to the quiz view page. Normally this is just a redirect, but
     * If we were in a secure window, we close this window, and reload the view window we came from.
     *
     * @param boolean $canpreview This affects whether we have to worry about secure window stuff.
     */
    public function back_to_view_page($canpreview, $message = '') {
        global $CFG, $OUTPUT, $PAGE;
        $url = $this->_hotpot->view_url();
        redirect($url, $message);
    }

    /**
     * Print a control to finish the review. Normally this is just a link, but if we are
     * in a secure window, it needs to be a button that does M.mod_hotpot.secure_window.close.
     *
     * @param boolean $canpreview This affects whether we have to worry about secure window stuff.
     */
    public function print_finish_review_link($canpreview, $return = false) {
        global $CFG;
        $output = '';
        $url = $this->_hotpot->view_url();
        $output .= '<div class="finishreview">';
        $output .= '<a href="' . $url . '">' . get_string('finishreview', 'quiz') . "</a>\n";
        $output .= "</div>\n";
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * @return bolean if this quiz is password protected.
     */
    public function password_required() {
        return !is_null($this->_passwordrule);
    }

    /**
     * Clear the flag in the session that says that the current user is allowed to do this quiz.
     */
    public function clear_password_access() {
        if (!is_null($this->_passwordrule)) {
            $this->_passwordrule->clear_access_allowed();
        }
    }

    /**
     * Actually ask the user for the password, if they have not already given it this session.
     * This function only returns is access is OK.
     *
     * @param boolean $canpreview used to enfore securewindow stuff.
     */
    public function do_password_check($canpreview) {
        if (!is_null($this->_passwordrule)) {
            $this->_passwordrule->do_password_check($canpreview, $this);
        }
    }

    /**
     * @return string if the quiz policies merit it, return a warning string to be displayed
     * in a javascript alert on the start attempt button.
     */
    public function confirm_start_attempt_message() {
        $hotpot = $this->_hotpot;
        if ($hotpot->attemptlimit) {
            return get_string('confirmstartattemptlimit','quiz', $hotpot->attemptlimit);
        }
        return '';
    }

    /**
     * Make some text into a link to review the quiz, if that is appropriate.
     *
     * @param string $linktext some text.
     * @param object $attempt the attempt object
     * @return string some HTML, the $linktext either unmodified or wrapped in a link to the review page.
     */
    public function make_review_link($attempt, $canpreview, $reviewoptions) {
        global $CFG;

    /// If review of responses is not allowed, or the attempt is still open, don't link.
        if (!$attempt->timefinish) {
            return '';
        }
        if (!$reviewoptions->responses) {
            $message = $this->cannot_review_message($reviewoptions, true);
            if ($message) {
                return '<span class="noreviewmessage">' . $message . '</span>';
            } else {
                return '';
            }
        }

        $linktext = get_string('review', 'quiz');

    /// It is OK to link, does it need to be in a secure window?
        return '<a href="' . $this->_hotpot->review_url($attempt->id) . '" title="' .
                get_string('reviewthisattempt', 'quiz') . '">' . $linktext . '</a>';
    }

    /**
     * If $reviewoptions->responses is false, meaning that students can't review this
     * attempt at the moment, return an appropriate string explaining why.
     *
     * @param object $reviewoptions as obtained from hotpot_get_reviewoptions.
     * @param boolean $short if true, return a shorter string.
     * @return string an appropraite message.
     */
    public function cannot_review_message($reviewoptions, $short = false) {
        $hotpot = $this->_hotpot;
        if ($short) {
            $langstrsuffix = 'short';
            $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        } else {
            $langstrsuffix = '';
            $dateformat = '';
        }
        if ($reviewoptions->quizstate == QUIZ_STATE_IMMEDIATELY) {
            return '';
        } else if ($reviewoptions->quizstate == QUIZ_STATE_OPEN && $hotpot->timeclose &&
                    ($hotpot->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_RESPONSES)) {
            return get_string('noreviewuntil' . $langstrsuffix, 'quiz', userdate($hotpot->timeclose, $dateformat));
        } else {
            return get_string('noreview' . $langstrsuffix, 'quiz');
        }
    }
}

/**
 * A base class that defines the interface for the various quiz access rules.
 * Most of the methods are defined in a slightly unnatural way because we either
 * want to say that access is allowed, or explain the reason why it is block.
 * Therefore instead of is_access_allowed(...) we have prevent_access(...) that
 * return false if access is permitted, or a string explanation (which is treated
 * as true) if access should be blocked. Slighly unnatural, but acutally the easist
 * way to implement this.
 */
abstract class hotpot_access_rule_base {
    protected $_hotpot;
    protected $_timenow;
    /**
     * Create an instance of this rule for a particular quiz.
     * @param object $hotpot the quiz we will be controlling access to.
     */
    public function __construct($hotpot, $timenow) {
        $this->_hotpot = $hotpot;
        $this->_timenow = $timenow;
    }
    /**
     * Whether or not a user should be allowed to start a new attempt at this quiz now.
     * @param integer $numattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return string false if access should be allowed, a message explaining the reason if access should be prevented.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return false;
    }
    /**
     * Whether or not a user should be allowed to start a new attempt at this quiz now.
     * @return string false if access should be allowed, a message explaining the reason if access should be prevented.
     */
    public function prevent_access() {
        return false;
    }
    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     */
    public function description() {
        return '';
    }
    /**
     * If this rule can determine that this user will never be allowed another attempt at
     * this quiz, then return true. This is used so we can know whether to display a
     * final score on the view page. This will only be called if there is not a currently
     * active attempt for this user.
     * @param integer $numattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return boolean true if this rule means that this user will never be allowed another
     * attempt at this quiz.
     */
    public function is_finished($numprevattempts, $lastattempt) {
        return false;
    }

    /**
     * If, becuase of this rule, the user has to finish their attempt by a certain time,
     * you should override this method to return the amount of time left in seconds.
     * @param object $attempt the current attempt
     * @param integer $timenow the time now. We don't use $this->_timenow, so we can
     * give the user a more accurate indication of how much time is left.
     * @return mixed false if there is no deadline, of the time left in seconds if there is one.
     */
    public function time_left($attempt, $timenow) {
        return false;
    }
}

/**
 * A rule controlling the number of attempts allowed.
 */
class num_attemptlimit_access_rule extends hotpot_access_rule_base {

    /**
     * description
     *
     * @return xxx
     */
    public function description()  {
        return get_string('attemptsallowedn', 'quiz', $this->_hotpot->attemptlimit);
    }

    /**
     * prevent_new_attempt
     *
     * @param xxx $numprevattempts
     * @param xxx $lastattempt
     * @return xxx
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt)  {
        if ($numprevattempts >= $this->_hotpot->attemptlimit) {
            return get_string('nomoreattempts', 'quiz');
        }
        return false;
    }

    /**
     * is_finished
     *
     * @param xxx $numprevattempts
     * @param xxx $lastattempt
     * @return xxx
     */
    public function is_finished($numprevattempts, $lastattempt)  {
        return $numprevattempts >= $this->_hotpot->attemptlimit;
    }
}

/**
 * A rule enforcing open and close dates.
 */
class open_close_date_access_rule extends hotpot_access_rule_base {

    /**
     * description
     *
     * @return xxx
     */
    public function description()  {
        $result = array();
        if ($this->_timenow < $this->_hotpot->timeopen) {
            $result[] = get_string('quiznotavailable', 'quiz', userdate($this->_hotpot->timeopen));
        } else if ($this->_hotpot->timeclose && $this->_timenow > $this->_hotpot->timeclose) {
            $result[] = get_string("quizclosed", "quiz", userdate($this->_hotpot->timeclose));
        } else {
            if ($this->_hotpot->timeopen) {
                $result[] = get_string('quizopenedon', 'quiz', userdate($this->_hotpot->timeopen));
            }
            if ($this->_hotpot->timeclose) {
                $result[] = get_string('quizcloseson', 'quiz', userdate($this->_hotpot->timeclose));
            }
        }
        return $result;
    }

    /**
     * prevent_access
     *
     * @return xxx
     */
    public function prevent_access()  {
        if ($this->_timenow < $this->_hotpot->timeopen ||
                    ($this->_hotpot->timeclose && $this->_timenow > $this->_hotpot->timeclose)) {
            return get_string('notavailable', 'quiz');
        }
        return false;
    }

    /**
     * is_finished
     *
     * @param xxx $numprevattempts
     * @param xxx $lastattempt
     * @return xxx
     */
    public function is_finished($numprevattempts, $lastattempt)  {
        return $this->_hotpot->timeclose && $this->_timenow > $this->_hotpot->timeclose;
    }

    /**
     * time_left
     *
     * @param xxx $attempt
     * @param xxx $timenow
     * @return xxx
     */
    public function time_left($attempt, $timenow)  {
        // If this is a teacher preview after the close date, do not show
        // the time.
        if ($attempt->preview && $timenow > $this->_hotpot->timeclose) {
            return false;
        }

        // Otherwise, return to the time left until the close date, providing
        // that is less than QUIZ_SHOW_TIME_BEFORE_DEADLINE
        if ($this->_hotpot->timeclose) {
            $timeleft = $this->_hotpot->timeclose - $timenow;
            if ($timeleft < QUIZ_SHOW_TIME_BEFORE_DEADLINE) {
                return $timeleft;
            }
        }
        return false;
    }
}

/**
 * A rule implementing the ipaddress check against the ->submet setting.
 */
class ipaddress_access_rule extends hotpot_access_rule_base {

    /**
     * prevent_access
     *
     * @return xxx
     */
    public function prevent_access()  {
        if (address_in_subnet(getremoteaddr(), $this->_hotpot->subnet)) {
            return false;
        } else {
            return get_string('subnetwrong', 'quiz');
        }
    }
}

/**
 * A rule representing the password check. It does not actually implement the check,
 * that has to be done directly in attempt.php, but this facilitates telling users about it.
 */
class password_access_rule extends hotpot_access_rule_base {

    /**
     * description
     *
     * @return xxx
     */
    public function description()  {
        return get_string('requirepasswordmessage', 'quiz');
    }
    /**
     * Clear the flag in the session that says that the current user is allowed to do this quiz.
     */
    public function clear_access_allowed() {
        global $SESSION;
        if (!empty($SESSION->passwordcheckedquizzes[$this->_hotpot->id])) {
            unset($SESSION->passwordcheckedquizzes[$this->_hotpot->id]);
        }
    }
    /**
     * Actually ask the user for the password, if they have not already given it this session.
     * This function only returns is access is OK.
     *
     * @param boolean $canpreview used to enfore securewindow stuff.
     * @param object $accessmanager the accessmanager calling us.
     */
    public function do_password_check($canpreview, $accessmanager) {
        global $CFG, $SESSION, $OUTPUT, $PAGE;

    /// We have already checked the password for this quiz this session, so don't ask again.
        if (!empty($SESSION->passwordcheckedquizzes[$this->_hotpot->id])) {
            return;
        }

    /// If the user cancelled the password form, send them back to the view page.
        if (optional_param('cancelpassword', false, PARAM_BOOL)) {
            $accessmanager->back_to_view_page($canpreview);
        }

    /// If they entered the right password, let them in.
        $enteredpassword = optional_param('quizpassword', '', PARAM_RAW);
        $validpassword = false;
        if (strcmp($this->_hotpot->password, $enteredpassword) === 0) {
            $validpassword = true;
        } else if (isset($this->_hotpot->extrapasswords)) {
            // group overrides may have additional passwords
            foreach ($this->_hotpot->extrapasswords as $password) {
                if (strcmp($password, $enteredpassword) === 0) {
                    $validpassword = true;
                    break;
                }
            }
        }
        if ($validpassword) {
            $SESSION->passwordcheckedquizzes[$this->_hotpot->id] = true;
            return;
        }

    /// User entered the wrong password, or has not entered one yet, so display the form.
        $output = '';

    /// Start the page and print the quiz intro, if any.
        $PAGE->set_title(format_string($this->_hotpot->get_hotpot_name()));
        echo $OUTPUT->header();

        if (trim(strip_tags($this->_hotpot->intro))) {
            $output .= $OUTPUT->box(format_module_intro('quiz', $this->_hotpot, $this->_hotpot->get_cmid()), 'generalbox', 'intro');
        }
        $output .= $OUTPUT->box_start('generalbox', 'passwordbox');

    /// If they have previously tried and failed to enter a password, tell them it was wrong.
        if (!empty($enteredpassword)) {
            $output .= '<p class="notifyproblem">' . get_string('passworderror', 'quiz') . '</p>';
        }

    /// Print the password entry form.
        $output .= '<p>' . get_string('requirepasswordmessage', 'quiz') . "</p>\n";
        $output .= '<form id="passwordform" method="post" action="' . $CFG->wwwroot .
                '/mod/quiz/startattempt.php" onclick="this.autocomplete=\'off\'">' . "\n";
        $output .= "<div>\n";
        $output .= '<label for="quizpassword">' . get_string('password') . "</label>\n";
        $output .= '<input name="quizpassword" id="quizpassword" type="password" value=""/>' . "\n";
        $output .= '<input name="cmid" type="hidden" value="' .
                $this->_hotpot->get_cmid() . '"/>' . "\n";
        $output .= '<input name="sesskey" type="hidden" value="' . sesskey() . '"/>' . "\n";
        $output .= '<input type="submit" value="' . get_string('ok') . '" />';
        $output .= '<input type="submit" name="cancelpassword" value="' .
                get_string('cancel') . '" />' . "\n";
        $output .= "</div>\n";
        $output .= "</form>\n";

    /// Finish page.
        $output .= $OUTPUT->box_end();

    /// return or display form.
        echo $output;
        echo $OUTPUT->footer();
        exit;
    }
}

/**
 * A rule representing the time limit. It does not actually restrict access, but we use this
 * class to encapsulate some of the relevant code.
 */
class time_limit_access_rule extends hotpot_access_rule_base {

    /**
     * description
     *
     * @return xxx
     */
    public function description()  {
        return get_string('quiztimelimit', 'quiz', format_time($this->_hotpot->timelimit));
    }

    /**
     * time_left
     *
     * @param xxx $attempt
     * @param xxx $timenow
     * @return xxx
     */
    public function time_left($attempt, $timenow)  {
        return $attempt->timestart + $this->_hotpot->timelimit - $timenow;
    }
}
