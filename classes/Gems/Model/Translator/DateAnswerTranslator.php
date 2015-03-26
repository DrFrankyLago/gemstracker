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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 14:46:04
 */
class Gems_Model_Translator_DateAnswerTranslator extends \Gems_Model_Translator_RespondentAnswerTranslator
{
    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    protected function findTokenFor(array $row)
    {
        if (isset($row[$this->patientNrField], $row[$this->orgIdField], $row['completion_date']) &&
                $row[$this->patientNrField] &&
                $row[$this->orgIdField] &&
                $row['completion_date']) {

            if ($row['completion_date'] instanceof \Zend_Date) {
                $compl = $row['completion_date']->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
            } else {
                $compl = $row['completion_date'];
            }

            $select = $this->db->select();
            $select->from('gems__tokens', array('gto_id_token'))
                    ->joinInner(
                            'gems__respondent2org',
                            'gto_id_respondent = gr2o_id_user AND gto_id_organization = gr2o_id_organization',
                            array()
                            )
                    ->where('gr2o_patient_nr = ?', $row[$this->patientNrField])
                    ->where('gr2o_id_organization = ?', $row[$this->orgIdField])
                    ->where('gto_id_survey = ?', $this->getSurveyId())
                    ->where('gto_valid_from <= ?', $compl)
                    ->where('(gto_valid_until >= ? OR gto_valid_until IS NULL)', $compl);

            $trackId = $this->getTrackId();
            if ($trackId) {
                $select->where('gto_id_track = ?', $trackId);
            }

            $select->order(new \Zend_Db_Expr("CASE
                WHEN gto_completion_time IS NULL AND gto_valid_from IS NOT NULL THEN 1
                WHEN gto_completion_time IS NULL AND gto_valid_from IS NULL THEN 2
                ELSE 3 END ASC"))
                    ->order('gto_completion_time DESC')
                    ->order('gto_valid_from ASC')
                    ->order('gto_round_order');

            $token = $this->db->fetchOne($select);

            if ($token) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        if (! $this->_targetModel instanceof \MUtil_Model_ModelAbstract) {
            throw new \MUtil_Model_ModelTranslateException(sprintf('Called %s without a set target model.', __FUNCTION__));
        }

        return parent::getFieldsTranslations();
    }

    /**
     * Returns an array of the field names that are required
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields()
    {
        return parent::getRequiredFields() + array(
            'completion_date'      => 'completion_date',
            );
    }
}
