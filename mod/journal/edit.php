<?php // $Id: edit.php,v 1.1 2008/04/30 10:26:59 skodak Exp $

    require_once("../../config.php");

    $id = required_param('id', PARAM_INT);    // Course Module ID

    if (! $cm = get_coursemodule_from_id('journal', $id)) {
        print_error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        print_error("Course is misconfigured");
    }

    require_login($course->id, false, $cm);

    if (isguest()) {
        print_error("Guests are not allowed to edit journals", '', $_SERVER["HTTP_REFERER"]);
    }

    if (! $journal = get_record("journal", "id", $cm->instance)) {
        print_error("Course module is incorrect");
    }

    $entry = get_record("journal_entries", "userid", $USER->id, "journal", $journal->id);


/// If data submitted, then process and store.

    if ($form = data_submitted()) {

        $timenow = time();

        //$form->text = clean_text($form->text, $form->format);

        if ($entry) {
            $newentry->id = $entry->id;
            $newentry->text = $form->text;
            $newentry->format = $form->format;
            $newentry->modified = $timenow;
            if (! update_record("journal_entries", $newentry)) {
                print_error("Could not update your journal");
            }
            add_to_log($course->id, "journal", "update entry", "view.php?id=$cm->id", "$newentry->id", $cm->id);
        } else {
            $newentry->userid = $USER->id;
            $newentry->journal = $journal->id;
            $newentry->text = $form->text;
            $newentry->format = $form->format;
            $newentry->modified = $timenow;
            if (! $newentry->id = insert_record("journal_entries", $newentry)) {
                print_error("Could not insert a new journal entry");
            }
            add_to_log($course->id, "journal", "add entry", "view.php?id=$cm->id", "$newentry->id", $cm->id);
        }

        redirect("view.php?id=$cm->id");
        die;
    }

/// Otherwise fill and print the form.

    $strjournal = get_string("modulename", "journal");
    $strjournals = get_string("modulenameplural", "journal");
    $stredit = get_string("edit");

    if ($usehtmleditor = can_use_richtext_editor()) {
        $defaultformat = FORMAT_HTML;
    } else {
        $defaultformat = FORMAT_MOODLE;
    }

    if (empty($entry)) {
        $entry->text = "";
        $entry->format = $defaultformat;
    }

    $navigation = build_navigation($stredit, $cm);
    print_header_simple(format_string($journal->name), "", $navigation, "",
                  "", true, "", navmenu($course, $cm));

    echo "<center>\n";

    print_simple_box( format_text($journal->intro, $journal->introformat) , "center");

    echo "<br />";

    include("edit.html");

    if ($usehtmleditor) {
        use_html_editor("text");
    }
    echo "</center>\n";
    print_footer($course);

?>
