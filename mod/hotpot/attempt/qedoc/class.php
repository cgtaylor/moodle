<?php

class hotpot_attempt_qedoc extends hotpot_attempt {

    public $detailsfield = 'data';
    public $xmlresultstag = 'quiz_results';

    // this output format does not use the QuizPort cache
    public $use_hotpot_cache = false;

    public $qedocplayerurl = 'http://www.qedoc.net/qqp/launch/qp.jnlp';

    /**
     * hotpot_attempt_qedoc
     *
     * @param xxx $hotpot (passed by reference)
     */
    function init($hotpot)  {
        $hotpot->navigation = hotpot::NAVIGATION_ORIGINAL;
        parent::init($hotpot);
    }

    /**
     * List of source types which this renderer can handle
     *
     * @return array of strings
     */
    public static function sourcetypes()  {
        return array('qedoc');
    }

    // functions to generate browser content

    function generate($cacheonly=false) {
        global $CFG, $QUIZPORT;

        if ($cacheonly) {
            return true;
        }

        $site = get_site();
        $cookies = array();
        $names = array(
            'MOODLEID_'.$CFG->sessioncookie,
            'MoodleSession'.$CFG->sessioncookie,
            'MoodleSessionTest'.$CFG->sessioncookie
        );
        foreach ($names as $name) {
            if (isset($_COOKIE[$name])) {
                $cookies[] = $name.'/'.$_COOKIE[$name];
            }
        }
        $custompostfields = array(
            'sesskey/'.sesskey(),
            'id/'.$QUIZPORT->quizattemptid
        );
        $params = array(
            'mod='.urlencode($this->hotpot->source->url),
            'suburl='.urlencode($CFG->wwwroot.'/mod/hotpot/view.php'),
            //'activity=99', // activity within a Qedoc module
            'onSubmit=close',
            'onAbandon=submitandclose',
            'lmstype=moodle',
            'lmsname='.urlencode($site->fullname),
            'cookie='.urlencode(implode(';', $cookies)),
            'custompostfields='.urlencode(implode(';', $custompostfields))
        );
        // the url that wil initiate the qedoc player
        $qedocurl = $this->qedocplayerurl.'?'.implode('&amp;', $params);

        // this will display OK in FF and works in IE
        // but IE will display a warning about downloading
        $showcontinuepage = true;

        if ($showcontinuepage) {
            $QUIZPORT->print_header();

            $name = format_string($this->name, true);
            print_heading($name);

            // Qedoc link for non-javascript browsers (and IE)
            $onclick = "var obj=document.getElementById('qedoclinkmsg');if(obj)obj.style.display='none';";
            $link = '<a id="qedoclink" href="'.$qedocurl.'" onclick="'.$onclick.'">'.$name.'</a>';
            $msg = "\n"
                .get_string('popupresource', 'resource')."\n"
                .'<span id="qedoclinkmsg"><br />'."\n"
                .get_string('popupresourcelink', 'resource', $link)."\n"
                .'</span>'."\n"
            ;
            print_box($msg, 'generalbox', 'centeredboxtable');
            print '<p> </p>'; // white space

            // continue
            $params = array('quizid'=>0, 'quizscoreid'=>0, 'quizattemptid'=>0, 'qnumber'=>0);
            $href = new moodle_url('/mod/hotpot/view.php', 'coursemoduleid', $params);
            $link = '<a href="'.$href.'">'.get_string('continue').'</a>';
            $msg = get_string('clicktocontinue', 'hotpot', $link);
            print_box($msg, 'generalbox', 'centeredboxtable');

            // When using javascript to redirect IE to the Qedoc launch page, we get:
            // "To help protect your security, Internet Explorer blocked this site
            // from downloading files to your computer. Click here for options."
            print "\n"
                .'<script type="text/javascript">'."\n"
                .'//<![CDATA['."\n"
                .'function hotpot_launch_qedoc() {'."\n"
                ."    if (navigator.userAgent.indexOf('MSIE')<0) {\n"
                ."        public obj = document.getElementById('qedoclinkmsg');\n"
                .'        if (obj) {'."\n"
                ."            obj.style.display = 'none';\n"
                .'        }'."\n"
                ."        public obj = document.getElementById('qedoclink');\n"
                .'        if (obj) {'."\n"
                .'            window.location = obj.href;'."\n"
                .'        }'."\n"
                .'    }'."\n"
                .'}'."\n"
                .$this->fix_onload('hotpot_launch_qedoc()')
                .'//]]>'."\n"
                .'</script>'."\n"
            ;

            $QUIZPORT->print_footer();
        } else {
            // redirect() calls clean_text() which converts onSubmit
            // to XonSubmit and onAbandon to XonAbandon.
            // We can get round this if we url-encode 'on' as '%6f%6e':
            // redirect(str_replace('on', '%6f%6e', $qedocurl));
            // However Qedoc won't recognize urlencoded param names,
            // so forget about using redirect()

            // I don't think the following will work because
            // Qedoc won't accept parameters it doesn't know about
            if (!empty($CFG->usesid) && !isset($_COOKIE[session_name()])) {
               $qedocurl = sid_process_url($qedocurl);
            }

            // This works on IE, without showing a security warning.
            // However, it leaves the browser showing the page with
            // link that launched Qedoc (e.g. course page), with no
            // indication of how to continue or what to do next :-(
            @header('Location: '.str_replace('&amp;', '&', $qedocurl));
            die;
        }
    }

    /**
     * store_details
     *
     * @param xxx $quizattempt (passed by reference)
     */
    function store_details(&$quizattempt)  {
        global $CFG, $DB, $QUIZPORT;

        // parse the attempt details as xml
        $details = xmlize($quizattempt->details);

        // this is the expected structure of the incoming results
        $groupnames = array(
            'metadata' => array(
                'id', 'name', 'title', 'filename'
            ),
            'cumulative_results' => array(
                'total_score', 'average_score', 'total_time',
                'accuracy', 'maximum', 'percentage', 'attempts'
            ),
            'latest_attempt' => array(
                'last_score', 'last_activity', 'last_time', 'last_size',
                'last_maxsize', 'last_right', 'last_wrong', 'last_indeterminate'
            )
        );

        if (! isset($details[$this->xmlresultstag]['#'])) {
            print get_string('qedocerror', 'hotpot', get_string('qedocnoxmltag', 'hotpot'));
            return;
        }

        $groups = &$details[$this->xmlresultstag]['#'];
        foreach ($groupnames as $groupname=>$fieldnames) {
            if (isset($groups[$groupname]['0']['#'])) {
                $group = &$groups[$groupname]['0']['#'];
                foreach ($fieldnames as $fieldname) {
                    if (isset($group[$fieldname]['0']['#'])) {
                        $quizattempt->$fieldname = $group[$fieldname]['0']['#'];
                    }
                }
                unset($group);
            }
        }

        // check filename matches basename(sourcefile)
        if (empty($quizattempt->filename)) {
            print get_string('qedocerror', 'hotpot', get_string('qedocnofilename', 'hotpot'));
            return;
        }
        if ($quizattempt->filename != basename($QUIZPORT->quiz->sourcefile)) {
            print get_string('qedocerror', 'hotpot', get_string('qedocwrongmodule', 'hotpot', $quizattempt->filename));
            return;
        }

        // we can assume the status is "completed"
        // because we started up the player using
        // onSubmit=close and onAbandon=submitandclose
        $status = hotpot::STATUS_COMPLETED;

        $i = 0;
        while (isset($groups['questions']['0']['#']['question'][$i]['#'])) {
            $qedoc_question = &$groups['questions']['0']['#']['question'][$i]['#'];

            // check we have an answer, i.e. ignore unanswered questions
            $qedoc_answer = &$qedoc_question['answer']['0']['#'];
            if (isset($qedoc_answer) && is_string($qedoc_answer) && strlen($qedoc_answer)) {

                // setup question object
                $question = (object)array(
                    'quizid' => $quizattempt->quizid,
                    'name' => $qedoc_question['QID']['0']['#'],
                    'md5key' =>  md5($qedoc_question['QID']['0']['#']),
                    'type' => 0, // $qedoc_question['type']['0']['#']
                    'text' =>  hotpot_string_id($qedoc_question['stimulus']['0']['#']),
                );

                if (! $question->id = $DB->get_field('hotpot_questions', 'id', array('quizid'=>$question->quizid, 'md5key'=>$question->md5key))) {
                    // add question record
                    if ($CFG->majorrelease<=1.9) {
                        $question->name = addslashes($question->name);
                    }
                    if (! $question->id = $DB->insert_record('hotpot_questions', $question)) {
                        print_error('error_insertrecord', 'hotpot', '', 'hotpot_questions');
                    }
                }

                // setup response object
                $response = (object)array(
                    'attemptid' => $quizattempt->id,
                    'questionid' => $question->id,
                    'score' => $qedoc_question['Points']['0']['#'],
                    'weighting' => 100, // $qedoc_question['Weighting']['0']['#']
                    'hints' => 0, 'clues' => 0, 'checks' => 0,
                    'correct' => '', 'wrong' => '', 'ignored' => ''
                );

                $string_id = hotpot_string_id($qedoc_answer);
                switch ($qedoc_question['Status']['0']['#']) {
                    case 2: // CORRECT
                    case 1: // PARTLYCORRECT
                        $response->correct = $string_id;
                        break;
                    case 0: // WRONG
                        $response->wrong = $string_id;
                        break;
                    case -1: // NOT_ANSWERED
                    case -2: // NOT_VIEWED
                    case -3: // INDETERMINATE
                    case -4: // NOT_FOR_CORRECTING
                    default:
                        $response->ignored = $string_id;
                }

                // add response record
                if(! $response->id = $DB->insert_record('hotpot_responses', $response)) {
                    print_error('error_insertrecord', 'hotpot', '', 'hotpot_responses');
                }
            } // end if answer
            unset($qedoc_question);
            unset($qedoc_answer);
            $i++;
        } // end while
        unset($groups);

        $quizattempt->status = $status;
        $quizattempt->score = $quizattempt->percentage;
        $quizattempt->duration = $quizattempt->total_time / 1000;
    }

    /**
     * redirect
     */
    function redirect()  {
        print get_string('qedocsavedresults', 'hotpot');
    }
}
