<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RefreshQuestion.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Task_Tracker_RefreshQuestion extends MUtil_Task_TaskAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_View
     */
    protected $view;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($surveyId = null, $questionId = null, $order = null)
    {
        $batch  = $this->getBatch();
        $survey = $this->loader->getTracker()->getSurvey($surveyId);

        // Now save the questions
        $answerModel   = $survey->getAnswerModel('en');
        $questionModel = new MUtil_Model_TableModel('gems__survey_questions');

        Gems_Model::setChangeFieldsByPrefix($questionModel, 'gsq');

        $label = $answerModel->get($questionId, 'label');
        /*
        if ($label instanceof MUtil_Html_HtmlInterface) {
            $label = $label->render($this->view);
        }
        // */

        $question['gsq_id_survey']   = $surveyId;
        $question['gsq_name']        = $questionId;
        $question['gsq_name_parent'] = $answerModel->get($questionId, 'parent_question');
        $question['gsq_order']       = $order;
        $question['gsq_type']        = $answerModel->getWithDefault($questionId, 'type', MUtil_Model::TYPE_STRING);
        $question['gsq_class']       = $answerModel->get($questionId, 'thClass');
        $question['gsq_group']       = $answerModel->get($questionId, 'group');
        $question['gsq_label']       = $label;
        $question['gsq_description'] = $answerModel->get($questionId, 'description');

        // MUtil_Echo::track($question);
        try {
            $questionModel->save($question);
        } catch (Exception $e) {
            $batch->addMessage(sprintf(
                    $this->_('Save failed for survey %s, question %s: %s'),
                    $survey->getName(),
                    $questionId,
                    $e->getMessage()
                    ));
        }

        $batch->addToCounter('checkedQuestions');
        if ($questionModel->getChanged()) {
            $batch->addToCounter('changedQuestions');
        }
        $batch->setMessage('questionschanged', sprintf(
                $this->_('%d of %d questions changed.'),
                $batch->getCounter('changedQuestions'),
                $batch->getCounter('checkedQuestions')));

        $options = $answerModel->get($questionId, 'multiOptions');
        if ($options) {
            $optionModel   = new MUtil_Model_TableModel('gems__survey_question_options');
            Gems_Model::setChangeFieldsByPrefix($optionModel, 'gsqo');

            $option['gsqo_id_survey'] = $surveyId;
            $option['gsqo_name']      = $questionId;
            $i = 0;

            // MUtil_Echo::track($options);
            foreach ($options as $key => $label) {
                $option['gsqo_order'] = $i;
                $option['gsqo_key']   = $key;
                $option['gsqo_label'] = $label;

                try {
                    $optionModel->save($option);
                } catch (Exception $e) {
                    $batch->addMessage(sprintf(
                            $this->_('Save failed for survey %s, question %s, option "%s" => "%s": %s'),
                            $survey->getName(),
                            $questionId,
                            $key,
                            $label,
                            $e->getMessage()
                            ));
                }

                $i++;
            }
            $batch->addToCounter('checkedOptions', count($options));
            $batch->addToCounter('changedOptions', $optionModel->getChanged());
            $batch->setMessage('optionschanged', sprintf(
                    $this->_('%d of %d options changed.'),
                    $batch->getCounter('changedOptions'),
                    $batch->getCounter('checkedOptions')));
        }

    }
}