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
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_quiz
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_branchedquiz\output;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/quiz/classes/output/edit_renderer.php');

use \mod_quiz\structure;
use \html_writer;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */
class bqedit_renderer extends \mod_quiz\output\edit_renderer {
	protected function initialise_editing_javascript(structure $structure,
            \question_edit_contexts $contexts, array $pagevars, \moodle_url $pageurl) {

        $config = new \stdClass();
        $config->resourceurl = '/mod/branchedquiz/edit_rest.php';
        $config->sectionurl = '/mod/branchedquiz/edit_rest.php';
        $config->pageparams = array();
        $config->questiondecimalpoints = $structure->get_decimal_places_for_question_marks();
        $config->pagehtml = $this->new_page_template($structure, $contexts, $pagevars, $pageurl);
        $config->addpageiconhtml = $this->add_page_icon_template($structure);

        $this->page->requires->yui_module('moodle-mod_quiz-toolboxes',
                'M.mod_quiz.init_resource_toolbox',
                array(array(
                        'courseid' => $structure->get_courseid(),
                        'quizid' => $structure->get_quizid(),
                        'ajaxurl' => $config->resourceurl,
                        'config' => $config,
                ))
        );
        unset($config->pagehtml);
        unset($config->addpageiconhtml);

        $this->page->requires->yui_module('moodle-mod_quiz-toolboxes',
                'M.mod_quiz.init_section_toolbox',
                array(array(
                        'courseid' => $structure,
                        'quizid' => $structure->get_quizid(),
                        'ajaxurl' => $config->sectionurl,
                        'config' => $config,
                ))
        );

        $this->page->requires->yui_module('moodle-mod_quiz-dragdrop', 'M.mod_quiz.init_section_dragdrop',
                array(array(
                        'courseid' => $structure,
                        'quizid' => $structure->get_quizid(),
                        'ajaxurl' => $config->sectionurl,
                        'config' => $config,
                )), null, true);

        $this->page->requires->yui_module('moodle-mod_quiz-dragdrop', 'M.mod_quiz.init_resource_dragdrop',
                array(array(
                        'courseid' => $structure,
                        'quizid' => $structure->get_quizid(),
                        'ajaxurl' => $config->resourceurl,
                        'config' => $config,
                )), null, true);

        // Require various strings for the command toolbox.
        $this->page->requires->strings_for_js(array(
                'clicktohideshow',
                'deletechecktype',
                'deletechecktypename',
                'edittitle',
                'edittitleinstructions',
                'emptydragdropregion',
                'hide',
                'markedthistopic',
                'markthistopic',
                'move',
                'movecontent',
                'moveleft',
                'movesection',
                'page',
                'question',
                'selectall',
                'show',
                'tocontent',
        ), 'moodle');

        $this->page->requires->strings_for_js(array(
                'addpagebreak',
                'confirmremovesectionheading',
                'confirmremovequestion',
                'dragtoafter',
                'dragtostart',
                'numquestionsx',
                'sectionheadingedit',
                'sectionheadingremove',
                'removepagebreak',
                'questiondependencyadd',
                'questiondependencyfree',
                'questiondependencyremove',
                'questiondependsonprevious',
        ), 'quiz');

        foreach (\question_bank::get_all_qtypes() as $qtype => $notused) {
            $this->page->requires->string_for_js('pluginname', 'qtype_' . $qtype);
        }

        return true;
    }

    public function section_remove_icon($section) {
        $title = get_string('sectionheadingremove', 'quiz', $section->heading);
        $url = new \moodle_url('/mod/branchedquiz/edit.php',
                array('sesskey' => sesskey(), 'removesection' => '1', 'sectionid' => $section->id));
        $image = $this->pix_icon('t/delete', $title);
        return $this->action_link($url, $image, null, array(
                'class' => 'cm-edit-action editing_delete', 'data-action' => 'deletesection'));
    }

    public function edit_menu_actions(structure $structure, $page,
            \moodle_url $pageurl, array $pagevars) {
        $questioncategoryid = question_get_category_id_from_pagevars($pagevars);
        static $str;
        if (!isset($str)) {
            $str = get_strings(array('addasection', 'addaquestion', 'addarandomquestion',
                    'addarandomselectedquestion', 'questionbank'), 'quiz');
        }

        // Get section, page, slotnumber and maxmark.
        $actions = array();

        // Add a new section to the add_menu if possible. This is always added to the HTML
        // then hidden with CSS when no needed, so that as things are re-ordered, etc. with
        // Ajax it can be relevaled again when necessary.
        $params = array('cmid' => $structure->get_cmid(), 'addsectionatpage' => $page);

        $actions['addasection'] = new \action_menu_link_secondary(
                new \moodle_url($pageurl, $params),
                new \pix_icon('t/add', $str->addasection, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str->addasection, array('class' => 'cm-edit-action addasection', 'data-action' => 'addasection')
        );

        // Add a new question to the quiz.
        $returnurl = new \moodle_url($pageurl, array('addonpage' => $page));
        $params = array('returnurl' => $returnurl->out_as_local_url(false),
                'cmid' => $structure->get_cmid(), 'category' => $questioncategoryid,
                'addonpage' => $page, 'appendqnumstring' => 'addquestion');

        $actions['addaquestion'] = new \action_menu_link_secondary(
            new \moodle_url('/question/addquestion.php', $params),
            new \pix_icon('t/add', $str->addaquestion, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->addaquestion, array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
        );

        // Call question bank.
        $icon = new \pix_icon('t/add', $str->questionbank, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        if ($page) {
            $title = get_string('addquestionfrombanktopage', 'quiz', $page);
        } else {
            $title = get_string('addquestionfrombankatend', 'quiz');
        }
        $attributes = array('class' => 'cm-edit-action questionbank',
                'data-header' => $title, 'data-action' => 'questionbank', 'data-addonpage' => $page);
        $actions['questionbank'] = new \action_menu_link_secondary($pageurl, $icon, $str->questionbank, $attributes);

        // Add a random question.
        $returnurl = new \moodle_url('/mod/branchedquiz/edit.php', array('cmid' => $structure->get_cmid(), 'data-addonpage' => $page));
        $params = array('returnurl' => $returnurl, 'cmid' => $structure->get_cmid(), 'appendqnumstring' => 'addarandomquestion');
        $url = new \moodle_url('/mod/branchedquiz/addrandom.php', $params);
        $icon = new \pix_icon('t/add', $str->addarandomquestion, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array('class' => 'cm-edit-action addarandomquestion', 'data-action' => 'addarandomquestion');
        if ($page) {
            $title = get_string('addrandomquestiontopage', 'quiz', $page);
        } else {
            $title = get_string('addrandomquestionatend', 'quiz');
        }
        $attributes = array_merge(array('data-header' => $title, 'data-addonpage' => $page), $attributes);
        $actions['addarandomquestion'] = new \action_menu_link_secondary($url, $icon, $str->addarandomquestion, $attributes);

        return $actions;
    }

    protected function random_question_form(\moodle_url $thispageurl, \question_edit_contexts $contexts, array $pagevars) {

        if (!$contexts->have_cap('moodle/question:useall')) {
            return '';
        }
        $randomform = new \quiz_add_random_form(new \moodle_url('/mod/branchedquiz/addrandom.php'),
                                 array('contexts' => $contexts, 'cat' => $pagevars['cat']));
        $randomform->set_data(array(
                'category' => $pagevars['cat'],
                'returnurl' => $thispageurl->out_as_local_url(true),
                'randomnumber' => 1,
                'cmid' => $thispageurl->param('cmid'),
        ));
        return html_writer::div($randomform->render(), 'randomquestionformforpopup');
    }
}
