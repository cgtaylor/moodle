<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
class editcategory_form extends moodleform {

    // form definition
    function definition() {
<<<<<<< HEAD
        global $CFG;
=======
        global $CFG, $DB;
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
        $mform =& $this->_form;
        $category = $this->_customdata['category'];
        $editoroptions = $this->_customdata['editoroptions'];

        // get list of categories to use as parents, with site as the first one
<<<<<<< HEAD
        $options = array(get_string('top'));
=======
        $options = array();
        if (has_capability('moodle/category:manage', get_system_context()) || $category->parent == 0) {
            $options[0] = get_string('top');
        }
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
        $parents = array();
        if ($category->id) {
            // Editing an existing category.
            make_categories_list($options, $parents, 'moodle/category:manage', $category->id);
<<<<<<< HEAD
=======
            if (empty($options[$category->parent])) {
                $options[$category->parent] = $DB->get_field('course_categories', 'name', array('id'=>$category->parent));
            }
>>>>>>> 54b7b5993fbd4386eb4eadb4f97da8d41dfa16bf
            $strsubmit = get_string('savechanges');
        } else {
            // Making a new category
            make_categories_list($options, $parents, 'moodle/category:manage');
            $strsubmit = get_string('createcategory');
        }

        $mform->addElement('select', 'parent', get_string('parentcategory'), $options);
        $mform->addElement('text', 'name', get_string('categoryname'), array('size'=>'30'));
        $mform->addRule('name', get_string('required'), 'required', null);
        $mform->addElement('editor', 'description_editor', get_string('description'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);
        if (!empty($CFG->allowcategorythemes)) {
            $themes = array(''=>get_string('forceno'));
            $allthemes = get_list_of_themes();
            foreach ($allthemes as $key=>$theme) {
                $themes[$key] = $theme->name;
            }
            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $category->id);

        $this->add_action_buttons(true, $strsubmit);
    }
}

