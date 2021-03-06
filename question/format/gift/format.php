<?php
//
///////////////////////////////////////////////////////////////
// The GIFT import filter was designed as an easy to use method
// for teachers writing questions as a text file. It supports most
// question types and the missing word format.
//
// Multiple Choice / Missing Word
//     Who's buried in Grant's tomb?{~Grant ~Jefferson =no one}
//     Grant is {~buried =entombed ~living} in Grant's tomb.
// True-False:
//     Grant is buried in Grant's tomb.{FALSE}
// Short-Answer.
//     Who's buried in Grant's tomb?{=no one =nobody}
// Numerical
//     When was Ulysses S. Grant born?{#1822:5}
// Matching
//     Match the following countries with their corresponding
//     capitals.{=Canada->Ottawa =Italy->Rome =Japan->Tokyo}
//
// Comment lines start with a double backslash (//).
// Optional question names are enclosed in double colon(::).
// Answer feedback is indicated with hash mark (#).
// Percentage answer weights immediately follow the tilde (for
// multiple choice) or equal sign (for short answer and numerical),
// and are enclosed in percent signs (% %). See docs and examples.txt for more.
//
// This filter was written through the collaboration of numerous
// members of the Moodle community. It was originally based on
// the missingword format, which included code from Thomas Robb
// and others. Paul Tsuchido Shew wrote this filter in December 2003.
//////////////////////////////////////////////////////////////////////////
// Based on default.php, included by ../import.php
/**
 * @package questionbank
 * @subpackage importexport
 */
class qformat_gift extends qformat_default {

    function provide_import() {
        return true;
    }

    function provide_export() {
        return true;
    }

    function export_file_extension() {
        return '.txt';
    }

    function answerweightparser(&$answer) {
        $answer = substr($answer, 1);                        // removes initial %
        $end_position  = strpos($answer, "%");
        $answer_weight = substr($answer, 0, $end_position);  // gets weight as integer
        $answer_weight = $answer_weight/100;                 // converts to percent
        $answer = substr($answer, $end_position+1);          // removes comment from answer
        return $answer_weight;
    }

    function commentparser($answer, $defaultformat) {
        $bits = explode('#', $answer, 2);
        $ans = $this->parse_text_with_format(trim($bits[0]), $defaultformat);
        if (count($bits) > 1) {
            $feedback = $this->parse_text_with_format(trim($bits[1]), $defaultformat);
        } else {
            $feedback = array('text' => '', 'format' => $defaultformat, 'files' => array());
        }
        return array($ans, $feedback);
    }

    function split_truefalse_comment($answer, $defaultformat) {
        $bits = explode('#', $answer, 3);
        $ans = $this->parse_text_with_format(trim($bits[0]), $defaultformat);
        if (count($bits) > 1) {
            $wrongfeedback = $this->parse_text_with_format(trim($bits[1]), $defaultformat);
        } else {
            $wrongfeedback = array('text' => '', 'format' => $defaultformat, 'files' => array());
        }
        if (count($bits) > 2) {
            $rightfeedback = $this->parse_text_with_format(trim($bits[2]), $defaultformat);
        } else {
            $rightfeedback = array('text' => '', 'format' => $defaultformat, 'files' => array());
        }
        return array($ans, $wrongfeedback, $rightfeedback);
    }

    function escapedchar_pre($string) {
        //Replaces escaped control characters with a placeholder BEFORE processing

        $escapedcharacters = array("\\:",    "\\#",    "\\=",    "\\{",    "\\}",    "\\~",    "\\n"  );  //dlnsk
        $placeholders      = array("&&058;", "&&035;", "&&061;", "&&123;", "&&125;", "&&126;", "&&010");  //dlnsk

        $string = str_replace("\\\\", "&&092;", $string);
        $string = str_replace($escapedcharacters, $placeholders, $string);
        $string = str_replace("&&092;", "\\", $string);
        return $string;
    }

    function escapedchar_post($string) {
        //Replaces placeholders with corresponding character AFTER processing is done
        $placeholders = array("&&058;", "&&035;", "&&061;", "&&123;", "&&125;", "&&126;", "&&010"); //dlnsk
        $characters   = array(":",     "#",      "=",      "{",      "}",      "~",      "\n"  ); //dlnsk
        $string = str_replace($placeholders, $characters, $string);
        return $string;
    }

    function check_answer_count($min, $answers, $text) {
        $countanswers = count($answers);
        if ($countanswers < $min) {
            $importminerror = get_string('importminerror', 'quiz');
            $this->error($importminerror, $text);
            return false;
        }

        return true;
    }

    protected function parse_text_with_format($text, $defaultformat = FORMAT_MOODLE) {
        $result = array(
            'text' => $text,
            'format' => $defaultformat,
            'files' => array(),
        );
        if (strpos($text, '[') === 0) {
            $formatend = strpos($text, ']');
            $result['format'] = $this->format_name_to_const(substr($text, 1, $formatend - 1));
            if ($result['format'] == -1) {
                $result['format'] = $defaultformat;
            } else {
                $result['text'] = substr($text, $formatend + 1);
            }
        }
        $result['text'] = trim($this->escapedchar_post($result['text']));
        return $result;
    }

    function readquestion($lines) {
    // Given an array of lines known to define a question in this format, this function
    // converts it into a question object suitable for processing and insertion into Moodle.

        $question = $this->defaultquestion();
        $comment = NULL;
        // define replaced by simple assignment, stop redefine notices
        $gift_answerweight_regex = '/^%\-*([0-9]{1,2})\.?([0-9]*)%/';

        // REMOVED COMMENTED LINES and IMPLODE
        foreach ($lines as $key => $line) {
            $line = trim($line);
            if (substr($line, 0, 2) == '//') {
                $lines[$key] = ' ';
            }
        }

        $text = trim(implode(' ', $lines));

        if ($text == '') {
            return false;
        }

        // Substitute escaped control characters with placeholders
        $text = $this->escapedchar_pre($text);

        // Look for category modifier
        if (preg_match('~^\$CATEGORY:~', $text)) {
            // $newcategory = $matches[1];
            $newcategory = trim(substr($text, 10));

            // build fake question to contain category
            $question->qtype = 'category';
            $question->category = $newcategory;
            return $question;
        }

        // QUESTION NAME parser
        if (substr($text, 0, 2) == '::') {
            $text = substr($text, 2);

            $namefinish = strpos($text, '::');
            if ($namefinish === false) {
                $question->name = false;
                // name will be assigned after processing question text below
            } else {
                $questionname = substr($text, 0, $namefinish);
                $question->name = trim($this->escapedchar_post($questionname));
                $text = trim(substr($text, $namefinish+2)); // Remove name from text
            }
        } else {
            $question->name = false;
        }


        // FIND ANSWER section
        // no answer means its a description
        $answerstart = strpos($text, '{');
        $answerfinish = strpos($text, '}');

        $description = false;
        if (($answerstart === false) and ($answerfinish === false)) {
            $description = true;
            $answertext = '';
            $answerlength = 0;
        } else if (!(($answerstart !== false) and ($answerfinish !== false))) {
            $this->error(get_string('braceerror', 'quiz'), $text);
            return false;
        } else {
            $answerlength = $answerfinish - $answerstart;
            $answertext = trim(substr($text, $answerstart + 1, $answerlength - 1));
        }

        // Format QUESTION TEXT without answer, inserting "_____" as necessary
        if ($description) {
            $questiontext = $text;
        } else if (substr($text, -1) == "}") {
            // no blank line if answers follow question, outside of closing punctuation
            $questiontext = substr_replace($text, "", $answerstart, $answerlength+1);
        } else {
            // inserts blank line for missing word format
            $questiontext = substr_replace($text, "_____", $answerstart, $answerlength+1);
        }

        // Get questiontext format from questiontext
        $text = $this->parse_text_with_format($questiontext);
        $question->questiontextformat = $text['format'];
        $question->generalfeedbackformat = $text['format'];
        $question->questiontext = $text['text'];

        // set question name if not already set
        if ($question->name === false) {
            $question->name = $question->questiontext;
        }

        // ensure name is not longer than 250 characters
        $question->name = shorten_text($question->name, 200);
        $question->name = strip_tags(substr($question->name, 0, 250));

        // determine QUESTION TYPE
        $question->qtype = NULL;

        // give plugins first try
        // plugins must promise not to intercept standard qtypes
        // MDL-12346, this could be called from lesson mod which has its own base class =(
        if (method_exists($this, 'try_importing_using_qtypes') && ($try_question = $this->try_importing_using_qtypes($lines, $question, $answertext))) {
            return $try_question;
        }

        if ($description) {
            $question->qtype = DESCRIPTION;

        } else if ($answertext == '') {
            $question->qtype = ESSAY;

        } else if ($answertext{0} == '#') {
            $question->qtype = NUMERICAL;

        } else if (strpos($answertext, '~') !== false)  {
            // only Multiplechoice questions contain tilde ~
            $question->qtype = MULTICHOICE;

        } else if (strpos($answertext, '=')  !== false
                && strpos($answertext, '->') !== false) {
            // only Matching contains both = and ->
            $question->qtype = MATCH;

        } else { // either TRUEFALSE or SHORTANSWER

            // TRUEFALSE question check
            $truefalse_check = $answertext;
            if (strpos($answertext, '#') > 0) {
                // strip comments to check for TrueFalse question
                $truefalse_check = trim(substr($answertext, 0, strpos($answertext,"#")));
            }

            $valid_tf_answers = array('T', 'TRUE', 'F', 'FALSE');
            if (in_array($truefalse_check, $valid_tf_answers)) {
                $question->qtype = TRUEFALSE;

            } else { // Must be SHORTANSWER
                $question->qtype = SHORTANSWER;
            }
        }

        if (!isset($question->qtype)) {
            $giftqtypenotset = get_string('giftqtypenotset', 'quiz');
            $this->error($giftqtypenotset, $text);
            return false;
        }

        switch ($question->qtype) {
            case DESCRIPTION:
                $question->defaultgrade = 0;
                $question->length = 0;
                return $question;
                break;
            case ESSAY:
                $question->fraction = 0;
                $question->feedback['text'] = '';
                $question->feedback['format'] = $question->questiontextformat;
                $question->feedback['files'] = array();
                return $question;
                break;
            case MULTICHOICE:
                if (strpos($answertext,"=") === false) {
                    $question->single = 0; // multiple answers are enabled if no single answer is 100% correct
                } else {
                    $question->single = 1; // only one answer allowed (the default)
                }
                $question->correctfeedback['text'] = '';
                $question->correctfeedback['format'] = $question->questiontextformat;
                $question->correctfeedback['files'] = array();
                $question->partiallycorrectfeedback['text'] = '';
                $question->partiallycorrectfeedback['format'] = $question->questiontextformat;
                $question->partiallycorrectfeedback['files'] = array();
                $question->incorrectfeedback['text'] = '';
                $question->incorrectfeedback['format'] = $question->questiontextformat;
                $question->incorrectfeedback['files'] = array();

                $answertext = str_replace("=", "~=", $answertext);
                $answers = explode("~", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                $countanswers = count($answers);

                if (!$this->check_answer_count(2, $answers, $text)) {
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // determine answer weight
                    if ($answer[0] == '=') {
                        $answer_weight = 1;
                        $answer = substr($answer, 1);

                    } else if (preg_match($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);

                    } else {     //default, i.e., wrong anwer
                        $answer_weight = 0;
                    }
                    list($question->answer[$key], $question->feedback[$key]) =
                            $this->commentparser($answer, $question->questiontextformat);
                    $question->fraction[$key] = $answer_weight;
                }  // end foreach answer

                //$question->defaultgrade = 1;
                //$question->image = "";   // No images with this format
                return $question;
                break;

            case MATCH:
                $answers = explode('=', $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (!$this->check_answer_count(2,$answers,$text)) {
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);
                    if (strpos($answer, "->") === false) {
                        $giftmatchingformat = get_string('giftmatchingformat','quiz');
                        $this->error($giftmatchingformat, $answer);
                        return false;
                        break 2;
                    }

                    $marker = strpos($answer, '->');
                    $question->subquestions[$key] = $this->parse_text_with_format(
                            substr($answer, 0, $marker), $question->questiontextformat);
                    $question->subanswers[$key] = trim($this->escapedchar_post(
                            substr($answer, $marker + 2)));
                }

                return $question;
                break;

            case TRUEFALSE:
                list($answer, $wrongfeedback, $rightfeedback) =
                        $this->split_truefalse_comment($answertext, $question->questiontextformat);

<<<<<<< HEAD
                if ($answer == "T" OR $answer == "TRUE") {
=======
                if ($answer['text'] == "T" OR $answer['text'] == "TRUE") {
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
                    $question->correctanswer = 1;
                    $question->feedbacktrue = $rightfeedback;
                    $question->feedbackfalse = $wrongfeedback;
                } else {
                    $question->correctanswer = 0;
                    $question->feedbacktrue = $wrongfeedback;
                    $question->feedbackfalse = $rightfeedback;
                }

                $question->penalty = 1;

                return $question;
                break;

            case SHORTANSWER:
                // SHORTANSWER Question
                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (!$this->check_answer_count(1, $answers, $text)) {
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer weight
                    if (preg_match($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);
                    } else {     //default, i.e., full-credit anwer
                        $answer_weight = 1;
                    }

                    list($answer, $question->feedback[$key]) = $this->commentparser(
                            $answer, $question->questiontextformat);

                    $question->answer[$key] = $answer['text'];
                    $question->fraction[$key] = $answer_weight;
                }

                return $question;
                break;

            case NUMERICAL:
                // Note similarities to ShortAnswer
                $answertext = substr($answertext, 1); // remove leading "#"

                // If there is feedback for a wrong answer, store it for now.
                if (($pos = strpos($answertext, '~')) !== false) {
                    $wrongfeedback = substr($answertext, $pos);
                    $answertext = substr($answertext, 0, $pos);
                } else {
                    $wrongfeedback = '';
                }

                $answers = explode("=", $answertext);
                if (isset($answers[0])) {
                    $answers[0] = trim($answers[0]);
                }
                if (empty($answers[0])) {
                    array_shift($answers);
                }

                if (count($answers) == 0) {
                    // invalid question
                    $giftnonumericalanswers = get_string('giftnonumericalanswers','quiz');
                    $this->error($giftnonumericalanswers, $text);
                    return false;
                    break;
                }

                foreach ($answers as $key => $answer) {
                    $answer = trim($answer);

                    // Answer weight
                    if (preg_match($gift_answerweight_regex, $answer)) {    // check for properly formatted answer weight
                        $answer_weight = $this->answerweightparser($answer);
                    } else {     //default, i.e., full-credit anwer
                        $answer_weight = 1;
                    }

                    list($answer, $question->feedback[$key]) = $this->commentparser(
                            $answer, $question->questiontextformat);
                    $question->fraction[$key] = $answer_weight;
                    $answer = $answer['text'];

                    //Calculate Answer and Min/Max values
                    if (strpos($answer,"..") > 0) { // optional [min]..[max] format
                        $marker = strpos($answer,"..");
                        $max = trim(substr($answer, $marker+2));
                        $min = trim(substr($answer, 0, $marker));
                        $ans = ($max + $min)/2;
                        $tol = $max - $ans;
                    } else if (strpos($answer, ':') > 0) { // standard [answer]:[errormargin] format
                        $marker = strpos($answer, ':');
                        $tol = trim(substr($answer, $marker+1));
                        $ans = trim(substr($answer, 0, $marker));
                    } else { // only one valid answer (zero errormargin)
                        $tol = 0;
                        $ans = trim($answer);
                    }

                    if (!(is_numeric($ans) || $ans = '*') || !is_numeric($tol)) {
                            $errornotnumbers = get_string('errornotnumbers');
                            $this->error($errornotnumbers, $text);
                        return false;
                        break;
                    }

                    // store results
                    $question->answer[$key] = $ans;
                    $question->tolerance[$key] = $tol;
                }

                if ($wrongfeedback) {
                    $key += 1;
                    $question->fraction[$key] = 0;
                    list($notused, $question->feedback[$key]) = $this->commentparser(
                            $wrongfeedback, $question->questiontextformat);
                    $question->answer[$key] = '*';
                    $question->tolerance[$key] = '';
                }

                return $question;
                break;

                default:
                    $this->error(get_string('giftnovalidquestion', 'quiz'), $text);
                return fale;
                break;

        }

    }

    function repchar($text, $notused = 0) {
        // Escapes 'reserved' characters # = ~ {) :
        // Removes new lines
        $reserved = array( '#', '=', '~', '{', '}', ':', "\n", "\r");
        $escaped =  array('\#','\=','\~','\{','\}','\:', '\n', '' );

        $newtext = str_replace($reserved, $escaped, $text);
        return $newtext;
    }

    /**
     * @param integer $format one of the FORMAT_ constants.
     * @return string the corresponding name.
     */
    function format_const_to_name($format) {
        if ($format == FORMAT_MOODLE) {
            return 'moodle';
        } else if ($format == FORMAT_HTML) {
            return 'html';
        } else if ($format == FORMAT_PLAIN) {
            return 'plain';
        } else if ($format == FORMAT_MARKDOWN) {
            return 'markdown';
        } else {
            return 'moodle';
        }
    }

    /**
     * @param integer $format one of the FORMAT_ constants.
     * @return string the corresponding name.
     */
    function format_name_to_const($format) {
        if ($format == 'moodle') {
            return FORMAT_MOODLE;
        } else if ($format == 'html') {
            return FORMAT_HTML;
        } else if ($format == 'plain') {
            return FORMAT_PLAIN;
        } else if ($format == 'markdown') {
            return FORMAT_MARKDOWN;
        } else {
            return -1;
        }
    }

    public function write_name($name) {
        return '::' . $this->repchar($name) . '::';
    }

    public function write_questiontext($text, $format, $defaultformat = FORMAT_MOODLE) {
        $output = '';
        if ($text != '' && $format != $defaultformat) {
            $output .= '[' . $this->format_const_to_name($format) . ']';
        }
        $output .= $this->repchar($text, $format);
        return $output;
    }

    function writequestion($question) {
        global $QTYPES, $OUTPUT;

        // Start with a comment
        $expout = "// question: $question->id  name: $question->name\n";

        // output depends on question type
        switch($question->qtype) {

        case 'category':
            // not a real question, used to insert category switch
            $expout .= "\$CATEGORY: $question->category\n";
            break;

        case DESCRIPTION:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            break;

        case ESSAY:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= "{}\n";
            break;

        case TRUEFALSE:
            $trueanswer = $question->options->answers[$question->options->trueanswer];
            $falseanswer = $question->options->answers[$question->options->falseanswer];
            if ($trueanswer->fraction == 1) {
                $answertext = 'TRUE';
                $rightfeedback = $this->write_questiontext($trueanswer->feedback,
                        $trueanswer->feedbackformat, $question->questiontextformat);
                $wrongfeedback = $this->write_questiontext($falseanswer->feedback,
                        $falseanswer->feedbackformat, $question->questiontextformat);
            } else {
                $answertext = 'FALSE';
                $rightfeedback = $this->write_questiontext($falseanswer->feedback,
                        $falseanswer->feedbackformat, $question->questiontextformat);
                $wrongfeedback = $this->write_questiontext($trueanswer->feedback,
                        $trueanswer->feedbackformat, $question->questiontextformat);
            }

            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= '{' . $this->repchar($answertext);
            if ($wrongfeedback) {
                $expout .= '#' . $wrongfeedback;
            } else if ($rightfeedback) {
                $expout .= '#';
            }
            if ($rightfeedback) {
                $expout .= '#' . $rightfeedback;
            }
            $expout .= "}\n";
            break;

        case MULTICHOICE:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= "{\n";
            foreach($question->options->answers as $answer) {
                if ($answer->fraction == 1) {
                    $answertext = '=';
                } else if ($answer->fraction == 0) {
                    $answertext = '~';
                } else {
                    $weight = $answer->fraction * 100;
                    $answertext = '~%' . $weight . '%';
                }
                $expout .= "\t" . $answertext . $this->write_questiontext($answer->answer,
                            $answer->answerformat, $question->questiontextformat);
                if ($answer->feedback != '') {
                    $expout .= '#' . $this->write_questiontext($answer->feedback,
                            $answer->feedbackformat, $question->questiontextformat);
                }
                $expout .= "\n";
            }
            $expout .= "}\n";
            break;

        case SHORTANSWER:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= "{\n";
            foreach($question->options->answers as $answer) {
                $weight = 100 * $answer->fraction;
                $expout .= "\t=%" . $weight . '%' . $this->repchar($answer->answer) .
                        '#' . $this->write_questiontext($answer->feedback,
                            $answer->feedbackformat, $question->questiontextformat) . "\n";
            }
            $expout .= "}\n";
            break;

        case NUMERICAL:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= "{#\n";
            foreach ($question->options->answers as $answer) {
                if ($answer->answer != '' && $answer->answer != '*') {
                    $weight = 100 * $answer->fraction;
                    $expout .= "\t=%" . $weight . '%' . $answer->answer . ':' .
                            (float)$answer->tolerance . '#' . $this->write_questiontext($answer->feedback,
                            $answer->feedbackformat, $question->questiontextformat) . "\n";
                } else {
                    $expout .= "\t~#" . $this->write_questiontext($answer->feedback,
                            $answer->feedbackformat, $question->questiontextformat) . "\n";
                }
            }
            $expout .= "}\n";
            break;

        case MATCH:
            $expout .= $this->write_name($question->name);
            $expout .= $this->write_questiontext($question->questiontext, $question->questiontextformat);
            $expout .= "{\n";
            foreach($question->options->subquestions as $subquestion) {
                $expout .= "\t=" . $this->repchar($this->write_questiontext($subquestion->questiontext, $subquestion->questiontextformat, $question->questiontextformat)) .
                        ' -> ' . $this->repchar($subquestion->answertext) . "\n";
            }
            $expout .= "}\n";
            break;

        default:
            // Check for plugins
            if ($out = $this->try_exporting_using_qtypes($question->qtype, $question)) {
                $expout .= $out;
            } else {
                $expout .= "Question type $question->qtype is not supported\n";
                echo $OUTPUT->notification(get_string('nohandler', 'qformat_gift',
                        $QTYPES[$question->qtype]->local_name()));
            }
        }

        // Add empty line to delimit questions
        $expout .= "\n";
        return $expout;
    }
}

