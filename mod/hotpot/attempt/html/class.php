<?php
class hotpot_attempt_html extends hotpot_attempt {

    // strings to mark beginning and end of submission form
    public $BeginSubmissionForm = '<!-- BeginSubmissionForm -->';
    public $EndSubmissionForm = '<!-- EndSubmissionForm -->';
    public $formid = 'store';

    /**
     * hotpot_attempt_html
     *
     * @param xxx $quiz (passed by reference)
     */
    function hotpot_attempt_html(&$quiz)  {
        parent::hotpot_attempt($quiz);
    }

    /**
     * fix_submissionform_old
     */
    function fix_submissionform_old()   {
        global $CFG;

        // remove previous submission form, if any
        $search = '/\s*('.$this->BeginSubmissionForm.')\s*(.*?)\s*('.$this->EndSubmissionForm.')/s';
        $this->bodycontent = preg_replace($search, '', $this->bodycontent);

        // set form params
        $params = array(
            'id' => $this->quizattemptid,
            'detail' => '0', 'status' => hotpot::STATUS_COMPLETED,
            'starttime' => '0', 'endtime' => '0', 'redirect' => '1',
        );
        if (! preg_match('/<(input|select)[^>]*name="'.$this->scorefield.'"[^>]*>/is', $this->bodycontent)) {
            $params[$this->scorefield] = '0';
        }

        // wrap submission form around content
        if ($this->usemoodletheme) {
            $align = ' class="continuebutton"';
        } else {
            $align = ' align="center"';
        }
        $this->bodycontent = ''
            .$this->BeginSubmissionForm."\n"
            .$this->print_form_start('view.php', $params, false, true, array('id' => $this->formid))
            .$this->EndSubmissionForm."\n"
            .$this->bodycontent."\n"
            .$this->BeginSubmissionForm."\n"
            .'<div'.$align.'><input type="submit" value="'.get_string('continue').'" /></div>'."\n"
            .$this->print_form_end(true)
            .$this->EndSubmissionForm."\n"
        ;
    }
}
