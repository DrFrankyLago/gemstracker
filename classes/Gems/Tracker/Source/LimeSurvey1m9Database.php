<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LimeSurvey1m9Database.php 461 2011-08-31 16:34:43Z mjong $
 */

/**
 * Class description of LimeSurvey1m9Database
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Tracker_Source_LimeSurvey1m9Database extends Gems_Tracker_Source_SourceAbstract
{
    const CACHE_TOKEN_INFO = 'tokenInfo';

    const LS_DB_DATE_FORMAT     = 'yyyy-MM-dd';
    const LS_DB_DATETIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';

    const QUESTIONS_TABLE    = 'questions';
    const SURVEY_TABLE       = 'survey_';
    const SURVEYS_LANG_TABLE = 'surveys_languagesettings';
    const SURVEYS_TABLE      = 'surveys';
    const TOKEN_TABLE        = 'tokens_';

    /**
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'private';

    /**
     *
     * @var array of Gems_Tracker_Source_LimeSurvey1m9FieldMap
     */
    private $_fieldMaps;

    /**
     *
     * @var array of string
     */
    private $_languageMap;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Return a fieldmap object
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language      Optional (ISO) Language, uses default language for survey when null
     * @return Gems_Tracker_Source_LimeSurvey1m9FieldMap
     */
    protected function _getFieldMap($sourceSurveyId, $language = null)
    {
        $language = $this->_getLanguage($sourceSurveyId, $language);
        // MUtil_Echo::track($language, $sourceSurveyId);

        if (! isset($this->_fieldMaps[$sourceSurveyId][$language])) {
            $this->_fieldMaps[$sourceSurveyId][$language] = new Gems_Tracker_Source_LimeSurvey1m9FieldMap(
                    $sourceSurveyId,
                    $language,
                    $this->getSourceDatabase(),
                    $this->translate,
                    $this->addDatabasePrefix(''));
        }

        return $this->_fieldMaps[$sourceSurveyId][$language];
    }

    /**
     * Returns the langauge to use for the survey when this language is specified.
     *
     * Uses the requested language if it exists for the survey, the default language for the survey otherwise
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language       (ISO) Language
     * @return string (ISO) Language
     */
    protected function _getLanguage($sourceSurveyId, $language)
    {
        if (! is_string($language)) {
            $language = (string) $language;
        }

        if (! isset($this->_languageMap[$sourceSurveyId][$language])) {
            if ($language && $this->_isLanguage($sourceSurveyId, $language)) {
                $this->_languageMap[$sourceSurveyId][$language] = $language;
            } else {
                $lsDb = $this->getSourceDatabase();

                $sql = 'SELECT language
                    FROM ' . $this->_getSurveysTableName() . '
                    WHERE sid = ?';

                $this->_languageMap[$sourceSurveyId][$language] = $lsDb->fetchOne($sql, $sourceSurveyId);
            }
        }

        return $this->_languageMap[$sourceSurveyId][$language];
    }

    /**
     * Looks up the LimeSurvey Survey Id
     *
     * @param int $surveyId
     * @return int
     */
    protected function _getSid($surveyId)
    {
        return $this->getSurveyData($surveyId, 'gsu_surveyor_id');
    }

    /**
     * The survey languages table contains the survey level texts per survey
     *
     * @return string Name of survey languages table
     */
    protected function _getSurveyLanguagesTableName()
    {
        return $this->addDatabasePrefix(self::SURVEYS_LANG_TABLE);
    }

    /**
     * There exists a survey table for each active survey. The table contains the answers to the survey
     *
     * @param int $sourceSurveyId Survey ID
     * @return string Name of survey table for this survey
     */
    protected function _getSurveyTableName($sourceSurveyId)
    {
        return $this->addDatabasePrefix(self::SURVEY_TABLE . $sourceSurveyId);
    }

    /**
     * The survey table contains one row per each survey in LS
     *
     * @return string Name of survey table
     */
    protected function _getSurveysTableName()
    {
        return $this->addDatabasePrefix(self::SURVEYS_TABLE);
    }

    /**
     * Replaces hyphen with underscore so LimeSurvey won't choke on it
     *
     * @param string $token
     * @return string
     */
    protected function _getToken($tokenId)
    {
        return strtr($tokenId, '-', '_');
    }

    /**
     * There exists a token table for each active survey with tokens.
     *
     * @param int $sourceSurveyId Survey ID
     * @return string Name of token table for this survey
     */
    protected function _getTokenTableName($sourceSurveyId)
    {
        return $this->addDatabasePrefix(self::TOKEN_TABLE . $sourceSurveyId);
    }

    /**
     * Check if the specified language is available in Lime Survey
     *
     * @param int $sourceSurveyId Survey ID
     * @param string $language       (ISO) Language
     * @return boolean True when the language is an existing language
     */
    protected function _isLanguage($sourceSurveyId, $language)
    {
        if ($language && strlen($language)) {
            // Check for availability of language
            $sql = 'SELECT surveyls_language FROM ' . $this->_getSurveyLanguagesTableName() . ' WHERE surveyls_survey_id = ? AND surveyls_language = ?';
            $lsDb = $this->getSourceDatabase();

            return $lsDb->fetchOne($sql, array($sourceSurveyId, $language));
        }

        return false;
    }

    /**
     * Check if the tableprefix exists in the source database, and change the status of this
     * adapter in the gems_sources table accordingly
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @return boolean  True if the source is active
     */
    public function checkSourceActive($userId)
    {
        // The only method to check if it is active is by getting all the tables,
        // since the surveys table may be empty so we just check for existence.
        $sourceDb  = $this->getSourceDatabase();
        $tables    = array_map('strtolower', $sourceDb->listTables());
        $tableName = $this->addDatabasePrefix(self::SURVEYS_TABLE, false); // Get name without database prefix.

        $active = strtolower(in_array($tableName, $tables));

        $values['gso_active'] = $active ? 1 : 0;
        $values['gso_status'] = $active ? 'Active' : 'Inactive';
        $values['gso_last_synch'] = new Zend_Db_Expr('CURRENT_TIMESTAMP');

        $this->_updateSource($values, $userId);

        return $active;
    }

    /**
     * Inserts the token in the source (if needed) and sets those attributes the source wants to set.
     *
     * @param Gems_Tracker_Token $token
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function copyTokenToSource(Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $language   = $this->_getLanguage($sourceSurveyId, $language);
        $lsDb       = $this->getSourceDatabase();
        $lsSurvLang = $this->_getSurveyLanguagesTableName();
        $lsSurveys  = $this->_getSurveysTableName();
        $lsTokens   = $this->_getTokenTableName($sourceSurveyId);
        $tokenId    = $this->_getToken($token->getTokenId());

        /********************************
         * Check survey existence / url *
         ********************************/

        // Lookup url information in surveyor, checks for survey being active as well.
        $sql = "SELECT surveyls_url
            FROM $lsSurveys INNER JOIN $lsSurvLang
                ON sid = surveyls_survey_id
             WHERE sid = ?
                AND surveyls_language = ?
                AND active='Y'
             LIMIT 1";
        $currentUrl = $lsDb->fetchOne($sql, array($sourceSurveyId, $language));

        // No field was returned
        if (false === $currentUrl) {
            throw new Gems_Tracker_Source_SurveyNotFoundException(sprintf('The survey with id %d for token %s does not exist.', $surveyId, $tokenId), sprintf('The Lime Survey id is %s', $sourceSurveyId));
        }

        /*****************************
         * Set the end_of_survey uri *
         *****************************/

        $newUrl = $this->util->getCurrentURI('ask/return/' . MUtil_Model::REQUEST_ID . '/{TOKEN}');

        // Make sure the url is set correctly in surveyor.
        if ($currentUrl != $newUrl) {

            //$where = $lsDb->quoteInto('surveyls_survey_id = ? AND ', $sourceSurveyId) .
            //    $lsDb->quoteInto('surveyls_language = ?', $language);

            $lsDb->update($lsSurvLang,
                array('surveyls_url' => $newUrl),
                array(
                    'surveyls_survey_id = ?' => $sourceSurveyId,
                    'surveyls_language = ?' =>  $language
                    ));

            if (Gems_Tracker::$verbose) {
                MUtil_Echo::r("From $currentUrl\n to $newUrl", "Changed return url for $language version of $surveyId.");
            }
        }

        /****************************************
         * Insert token in table (if not there) *
         ****************************************/

        $values['completed']   = 'N';  // Apparently it is possible to have this value filled without a survey questionnaire.
        $values['attribute_1'] = (string) $token->getRespondentId();
        $values['attribute_2'] = (string) $token->getOrganizationId();
        $values['attribute_3'] = (string) $token->getConsentCode();

        $result = 0;
        if ($oldValues = $lsDb->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", $tokenId)) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (Gems_Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    MUtil_Echo::r($echo, "Updated limesurvey values for $tokenId");
                }

                $result = $lsDb->update($lsTokens, $values, array('token = ?' => $tokenId));
            }
        } else {
            if (Gems_Tracker::$verbose) {
                MUtil_Echo::r($values, "Inserted $tokenId into limesurvey");
            }
            $values['token'] = $tokenId;

            $result = $lsDb->insert($lsTokens, $values);
        }

        if ($result) {
            //If we have changed something, invalidate the cache
            $token->cacheReset();
        }

        return $result;
    }

    /**
     * Returns a field from the raw answers as a date object.
     *
     * A seperate function as only the source knows what format the date/time value has.
     *
     * @param string $fieldName Name of answer field
     * @param Gems_Tracker_Token  $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return MUtil_Date date time or null
     */
    public function getAnswerDateTime($fieldName, Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $answers = $token->getRawAnswers();

        if (isset($answers[$fieldName]) && $answers[$fieldName]) {
            if (Zend_Date::isDate($answers[$fieldName], self::LS_DB_DATETIME_FORMAT)) {
                return new MUtil_Date($answers[$fieldName], self::LS_DB_DATETIME_FORMAT);
            }
            if (Zend_Date::isDate($answers[$fieldName], self::LS_DB_DATE_FORMAT)) {
                return new MUtil_Date($answers[$fieldName], self::LS_DB_DATE_FORMAT);
            }
            if (Gems_Tracker::$verbose)  {
                MUtil_Echo::r($answers[$fieldName], 'Missed answer date value:');
            }
        }
    }

    /**
     * Gets the time the survey was completed according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return MUtil_Date date time or null
     */
    public function getCompletionTime(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        if ($token->cacheHas('submitdate')) {
            // Use cached value when it exists
            $submitDate = $token->cacheGet('submitdate');

        } else {
            if ($token->hasAnswersLoaded()) {
                // Use loaded answers when loaded
                $submitDate = $this->getAnswerDateTime('submitdate', $token, $surveyId, $sourceSurveyId);

            } else {
                if ($token->cacheHas(self::CACHE_TOKEN_INFO)) {
                    // Use token info when loaded to prevent extra query if not needed
                    $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);

                    $query = isset($tokenInfo['completed']) && ($tokenInfo['completed'] != 'N');
                } else {
                    $query = true;
                }

                if ($query) {
                    if (null === $sourceSurveyId) {
                        $sourceSurveyId = $this->_getSid($surveyId);
                    }

                    $lsDb       = $this->getSourceDatabase();
                    $lsSurvey   = $this->_getSurveyTableName($sourceSurveyId);
                    $tokenId    = $this->_getToken($token->getTokenId());

                    $submitDate = $lsDb->fetchOne("SELECT submitdate FROM $lsSurvey WHERE token = ? LIMIT 1", $tokenId);

                    if ($submitDate) {
                        if (Zend_Date::isDate($submitDate, self::LS_DB_DATETIME_FORMAT)) {
                            $submitDate = new MUtil_Date($submitDate, self::LS_DB_DATETIME_FORMAT);
                        } else {
                            $submitDate = false; // Null does not trigger cacheHas()
                        }
                    }
                } else {
                    $submitDate = false; // Null does not trigger cacheHas()
                }
            }
            $token->cacheSet('submitdate', $submitDate);
        }

        return $submitDate instanceof MUtil_Date ? $submitDate : null;
    }

    /**
     * Returns an array containing fieldname => label for each date field in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getDatesList($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        // Not a question but it is a valid date choice
        $results['submitdate'] = $this->translate->_('Submitdate');

        $results = $results + $this->_getFieldMap($sourceSurveyId, $language)->getQuestionList('D');

        return $results;
    }

    /**
     * Returns an array of arrays with the structure:
     *      question => string,
     *      class    => question|question_sub
     *      group    => is for grouping
     *      type     => (optional) source specific type
     *      answers  => string for single types,
     *                  array for selection of,
     *                  nothing for no answer
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Nested array
     */
    public function getQuestionInformation($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        return $this->_getFieldMap($sourceSurveyId, $language)->getQuestionInformation();
    }

    /**
     * Returns an array containing fieldname => label for each answerable question in the survey.
     *
     * Used in dropdown list etc..
     *
     * @param string $language   (ISO) language string
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array fieldname => label
     */
    public function getQuestionList($language, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        return $this->_getFieldMap($sourceSurveyId, $language)->getQuestionList();
    }

    /**
     * Returns the answers in simple raw array format, without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param string $tokenId Gems Token Id
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Field => Value array
     */
    public function getRawTokenAnswerRow($tokenId, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb   = $this->getSourceDatabase();
        $lsTab  = $this->_getSurveyTableName($sourceSurveyId);
        $token  = $this->_getToken($tokenId);

        try {
            $values = $lsDb->fetchRow("SELECT * FROM $lsTab WHERE token = ?", $token);
        } catch (Zend_Db_Statement_Exception $exception) {
            $this->logger->logError($exception, $this->request);
            $values = false;
        }

        if ($values) {
            return $this->_getFieldMap($sourceSurveyId)->mapKeysToTitles($values);
        } else {
            return array();
        }
    }

    /**
     * Returns the answers of multiple tokens in simple raw nested array format,
     * without value processing etc.
     *
     * Function may return more fields than just the answers.
     *
     * @param array $filter XXXXX
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return array Of nested Field => Value arrays indexed by tokenId
     */
    public function getRawTokenAnswerRows(array $filter, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb = $this->getSourceDatabase();
        $lsSurveyTable = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenTable  = $this->_getTokenTableName($sourceSurveyId);
        $tokenField    = $lsSurveyTable . '.token';

        $quotedTokenTable  = $lsDb->quoteIdentifier($lsTokenTable . '.token');
        $quotedSurveyTable = $lsDb->quoteIdentifier($lsSurveyTable . '.token');

        $select = $lsDb->select();
        $select->from($lsTokenTable, array('respondentid'   => 'attribute_1',
                                           'organizationid' => 'attribute_2',
                                           'consentcode'    => 'attribute_3'))
               ->join($lsSurveyTable, $quotedTokenTable . ' = ' . $quotedSurveyTable);

        //Now process the filters
        if (is_array($filter)) {
            //first preprocess the tokens
            if (isset($filter['token'])) {
                foreach ((array) $filter['token'] as $key => $tokenId) {
                    $token = $this->_getToken($tokenId);

                    $originals[$token] = $tokenId;
                    $filter[$tokenField][$key] = $token;
                }
                unset($filter['token']);
            }

            //now map the attributes to the right fields
            if (isset($filter['respondentid'])) {
                $filter['attribute_1'] = $filter['respondentid'];
                unset($filter['respondentid']);
            }
            if (isset($filter['organizationid'])) {
                $filter['attribute_2'] = $filter['organizationid'];
                unset($filter['organizationid']);
            }
            if (isset($filter['consentcode'])) {
                $filter['attribute_3'] = $filter['consentcode'];
                unset($filter['consentcode']);
            }
        }

        foreach ($filter as $field => $values) {
            $field = $lsDb->quoteIdentifier($field);
            if (is_array($values)) {
                $select->where("$field IN (?)", array_values($values));
            } else {
                $select->where("$field = ?", $values);
            }
        }

        if (Gems_Tracker::$verbose) {
            MUtil_Echo::r($select->__toString(), 'Select');
        }

        $rows = $select->query()->fetchAll(Zend_Db::FETCH_ASSOC);
        $results = array();
        //@@TODO: check if we really need this, or can just change the 'token' field to have the 'original'
        //        this way other sources that don't perform changes on the token field don't have to loop
        //        over this field. The survey(answer)model could possibly perform the translation for this source
        if ($rows) {
            if (isset($filter[$tokenField])) {
                foreach ($rows as $values) {
                    $token = $originals[$values['token']];
                    $results[$token] = $this->_getFieldMap($sourceSurveyId)->mapKeysToTitles($values);
                }
                return $results;
            } else {
                //@@TODO If we do the mapping in the select statement, maybe we can gain some performance here
                foreach ($rows as $values) {
                    $results[] = $this->_getFieldMap($sourceSurveyId)->mapKeysToTitles($values);
                }
                return $results;
            }
        }

        return array();
    }

    /**
     * Gets the time the survey was started according to the source
     *
     * A source always return null when it does not know this time (or does not know
     * it well enough). In the case Gems_Tracker_Token will do it's best to keep
     * track by itself.
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return MUtil_Date date time or null
     */
    public function getStartTime(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        // Always return null!
        // The 'startdate' field is the time of the first save, not the time the user started
        // so Lime Survey does not contain this value.
        return null;
    }

    /**
     * Returns a model for the survey answers
     *
     * @param Gems_Tracker_Survey $survey
     * @param string $language Optional (ISO) language string
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return MUtil_Model_ModelAbstract
     */
    public function getSurveyAnswerModel(Gems_Tracker_Survey $survey, $language = null, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($survey->getSurveyId());
        }
        $language   = $this->_getLanguage($sourceSurveyId, $language);

        $model    = $this->tracker->getSurveyModel($survey, $this);
        $fieldMap = $this->_getFieldMap($sourceSurveyId, $language)->applyToModel($model);

        return $model;
    }

    /**
     * Retrieve all fields stored in the token table, and store them in the tokencache
     *
     * @param Gems_Tracker_Token $token
     * @param type $surveyId
     * @param type $sourceSurveyId
     * @param array $fields
     * @return type
     */
    public function getTokenInfo(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId, array $fields = null) {
        if (! $token->cacheHas(self::CACHE_TOKEN_INFO)) {
            if (null === $sourceSurveyId) {
                $sourceSurveyId = $this->_getSid($surveyId);
            }

            $db       = $this->getSourceDatabase();
            $lsTokens = $this->_getTokenTableName($sourceSurveyId);
            $tokenId  = $this->_getToken($token->getTokenId());

            $sql = 'SELECT *
                FROM ' . $lsTokens . '
                WHERE token = ? LIMIT 1';

            try {
                $result = $this->getSourceDatabase()->fetchRow($sql, $tokenId);
            } catch (Zend_Db_Statement_Exception $exception) {
                $this->logger->logError($exception, $this->request);
                $result = false;
            }

            $token->cacheSet(self::CACHE_TOKEN_INFO, $result);
        } else {
            $result = $token->cacheGet(self::CACHE_TOKEN_INFO);
        }

        if ($fields !== null) $result = array_intersect_key((array) $result, array_flip ($fields));

        return $result;
    }

    /**
     * Returns the url that (should) start the survey for this token
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param string $language
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return string The url to start the survey
     */
    public function getTokenUrl(Gems_Tracker_Token $token, $language, $surveyId, $sourceSurveyId)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }
        $tokenId = $this->_getToken($token->getTokenId());

        if ($this->_isLanguage($sourceSurveyId, $language)) {
            $langUrl = '&lang=' . $language;
        } else {
            $langUrl = '';
        }

        // mgzdev.erasmusmc.nl/incant/index.php?sid=1&token=o7l9_b8z2
        return $this->getBaseUrl() . '/index.php?sid=' . $sourceSurveyId . '&token=' . $tokenId . $langUrl;
    }


    /**
     * Checks whether the token is in the source.
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean
     */
    public function inSource(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        return (boolean) $tokenInfo;
    }

    /**
     * Returns true if the survey was completed according to the source
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param int $surveyId Gems Survey Id
     * @param $answers array Field => Value array, can be empty
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @return boolean True if the token has completed
     */
    public function isCompleted(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null)
    {
        $tokenInfo = $this->getTokenInfo($token, $surveyId, $sourceSurveyId);
        if (isset($tokenInfo['completed']) && $tokenInfo['completed'] != 'N') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the answers passed on.
     *
     * @param Gems_Tracker_Token $token Gems token object
     * @param $answers array Field => Value array
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     */
    public function setRawTokenAnswers(Gems_Tracker_Token $token, array $answers, $surveyId, $sourceSurveyId = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb      = $this->getSourceDatabase();
        $lsTab     = $this->_getSurveyTableName($sourceSurveyId);
        $lsTokenId = $this->_getToken($token->getTokenId());

        $answers = $this->_getFieldMap($sourceSurveyId)->mapTitlesToKeys($answers);

        if ($lsDb->fetchOne("SELECT token FROM $lsTab WHERE token = ?", $lsTokenId)) {
            $where = $lsDb->quoteInto("token = ?", $lsTokenId);
            $lsDb->update($lsTab, $answers, $where);
        } else {
            $current = new Zend_Db_Expr('CURRENT_TIMESTAMP');

            $answers['token'] = $lsTokenId;
            $answers['startlanguage'] = $this->locale->getLanguage();
            $answers['datestamp'] = $current;
            $answers['startdate'] = $current;

            $lsDb->insert($lsTab, $answers);

        }
    }

    /**
     * Updates the gems database with the latest information about the surveys in this source adapter
     *
     * @param int $userId    Id of the user who takes the action (for logging)
     * @param bool $updateTokens Wether the tokens should be updated or not, default is true
     * @return array Returns an array of messages
     */
    public function synchronizeSurveys($userId, $updateTokens = true)
    {
        $lsDb          = $this->getSourceDatabase();
        $messages      = array();
        $source_id     = $this->getId();
        $surveys_table = $this->_getSurveysTableName();
        $s_langs_table = $this->_getSurveyLanguagesTableName();
        $token_library = $this->tracker->getTokenLibrary();

        if ($updateTokens) {
            if ($count = $this->updateTokens($userId)) {
                $messages[] = sprintf($this->translate->_('Updated %d Gems tokens to new token definition.'), $count);
            }
        }

        $sql = '
            SELECT sid, surveyls_title AS short_title, surveyls_description AS description, active, datestamp, ' . $this->_anonymizedField . '
                FROM ' . $surveys_table . ' INNER JOIN ' . $s_langs_table . '
                    ON sid = surveyls_survey_id AND language = surveyls_language
                ORDER BY surveyls_title';

        $surveyor_surveys = $lsDb->fetchAssoc($sql);

        ////////////////////////////////////////////////////
        // First check for surveys that have disappeared. //
        ////////////////////////////////////////////////////
        if ($surveyor_surveys) {
            // Get the first elements in an array
            $surveyor_sids = array_map('reset', $surveyor_surveys);

            foreach($this->_updateGemsSurveyExists($surveyor_sids, $userId) as $surveyId => $title) {
                $messages[] = sprintf($this->translate->_('The \'%s\' survey is no longer active. The survey was removed from LimeSurvey!'), $title);
            }
        }

        ////////////////////////////////////
        // Check for updates and inserts. //
        ////////////////////////////////////
        foreach ($surveyor_surveys as $surveyor_survey)
        {
            $sid =  $surveyor_survey['sid'];

            $survey = $this->tracker->getSurveyBySourceId($sid, $source_id);

            $surveyor_status = '';
            if ($surveyor_survey[$this->_anonymizedField] == 'Y') {
                $surveyor_status .= 'Uses anonymous answers. ';
            } elseif ($surveyor_survey[$this->_anonymizedField] !== 'N') {
                //This is for the case that $this->_anonymizedField is empty, we show an update statement.
                //The answers already in the table can only be linked to the repsonse based on the completion time
                //this requires a manual action as token table only hold minuts while survey table holds seconds
                //and we might have responses with the same timestamp.
                $update = "UPDATE " . $this->_getSurveysTableName() . " SET `" . $this->_anonymizedField . "` = 'N' WHERE sid = " . $sid . ';';
                $update .= "ALTER TABLE " . $this->_getSurveyTableName($sid) . " ADD `token` varchar(36) default NULL;";
                MUtil_Echo::r($update);
            }
            if ($surveyor_survey['datestamp'] == 'N') {
                $surveyor_status .= 'Not date stamped. ';
            }
            if ($surveyor_survey['active'] == 'Y') {
                // I needed this code once to restore from an error, left it in just in case
                //
                // Restore tokens, set attribute_1 to gto_id_respondent_track
                /*
                $sql = 'SELECT count(*) FROM ' . $this->getSurveyTableName($sid);
                if ($lsDb->fetchOne($sql)) {
                    $sql = 'SELECT gto_id_token, gto_id_respondent, gto_id_organization, gto_completion_time
                        FROM gems__tokens INNER JOIN gems__surveys ON gto_id_survey = gsu_id_survey
                        WHERE gto_in_source = 1 AND gsu_surveyor_id = ?
                        ORDER BY gto_id_organization, gto_completion_time';

                    $gemsTokens = $this->_gemsDb->fetchAll($sql, $sid);

                    $sql = 'SELECT tid, attribute_1, attribute_2, completed
                        FROM ' . $this->getTokenTableName($sid) . '
                        ORDER BY attribute_2, completed';

                    $lsTokens = $lsDb->fetchAll($sql);

                    $gemsToken = reset($gemsTokens);
                    foreach ($lsTokens as $lsToken) {

                        $tokenData = array('token' => $this->limesurveyToken($gemsToken['gto_id_token']));
                        $lsDb->update($this->getSurveyTableName($sid), $tokenData, array('id = ?' => $lsToken['tid']));

                        // $token['attribute_1'] = $gemsToken['gto_id_respondent'];
                        $lsDb->update($this->getTokenTableName($sid), $tokenData, array('tid = ?' => $lsToken['tid']));

                        $gemsToken = next($gemsTokens);
                    }
                } // */

                $surveyor_active = true;
                try {
                    $sql = 'SHOW COLUMNS FROM ' . $this->_getTokenTableName($sid);
                    $tokenTable = $lsDb->fetchAssoc($sql);

                    if ($tokenTable) {
                        $lengths = array();
                        if (preg_match('/\(([^\)]+)\)/', $tokenTable['token']['Type'], $lengths)) {
                            $tokenLength = $lengths[1];
                        } else {
                            $tokenLength = 0;
                        }
                        if ($tokenLength < $token_library->getLength()) {
                            $surveyor_status .= 'Token field length is too short. ';
                        }

                        $attrCount = 0;
                        for ($i = 1; $i < 10; $i++) {
                            $attrFields[$i] = isset($tokenTable['attribute_' . $i]);
                            $attrCount += $attrFields[$i] ? 1 : 0;
                        }
                        $neededAttr = (3 - $attrCount);
                        if ($neededAttr > 0) {
                            // Not enough attribute fields
                            if (1 == $neededAttr) {
                                $surveyor_status .= '1 extra token attribute field required. ';
                            } else {
                                $surveyor_status .= $neededAttr . ' extra token attribute fields required. ';
                            }
                        } else {
                            // Are the names OK
                            for ($i = 1; $i < 4; $i++) {
                                if (!$attrFields[$i]) {
                                    $surveyor_status .= sprintf('Token attribute field %d is missing. ', $i);
                                }
                            }
                        }
                    }

                    if ($updateTokens && (! $surveyor_status)) {
                        // Check for changes in the token definitions and for
                        // Gems tokens that should be LS tokens ('_' instead of '-')
                        $from = $token_library->getFrom() . '-';

                        $sqlTail = ' SET `token` = ' . $this->_getTokenFromToSql($from, $token_library->getTo() . '_', 'token') .
                            ' WHERE ' . $this->_getTokenFromSqlWhere($from, 'token');

                        $sql = 'UPDATE ' . $this->_getTokenTableName($sid) . $sqlTail;
                        // MUtil_Echo::pre($sql);

                        if ($count = $lsDb->query($sql)->rowCount()) {
                            // Only update surveys table if there were tokens
                            $sql = 'UPDATE ' . $this->_getSurveyTableName($sid) . $sqlTail;
                            $lsDb->query($sql);
                            // MUtil_Echo::pre($sql);

                            $messages[] = sprintf($this->translate->plural('Updated %d token to new token definition in survey \'%s\'.', 'Updated %d tokens to new token definition in survey \'%s\'.', $count), $count, $survey->getName());
                        }
                    }
                } catch (Zend_Exception $e) {
                    $surveyor_status .= 'No token table created. ';
                }

            } else {
                $surveyor_active = false;
                $surveyor_status .= 'Not active. ';
            }
            $surveyor_title = substr($surveyor_survey['short_title'], 0, 100);
            $values = array();

            if ($survey->exists) {   // Update
                if ($survey->isActiveInSource() != $surveyor_active) {
                    $values['gsu_surveyor_active'] = $surveyor_active ? 1 : 0;

                    $messages[] = sprintf($this->translate->_('The status of the \'%s\' survey has changed.'), $survey->getName());
                }

                // Reset to inactive if the surveyor survey has become inactive.
                if ($survey->isActive() && $surveyor_status) {
                    $values['gsu_active'] = 0;
                    $messages[] = sprintf($this->translate->_('Survey \'%s\' IS NO LONGER ACTIVE!!!'), $survey->getName());
                }

                if (substr($surveyor_status,  0,  127) != (string) $survey->getStatus()) {
                    if ($surveyor_status) {
                        $values['gsu_status'] = substr($surveyor_status,  0,  127);
                        $messages[] = sprintf($this->translate->_('The status of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $data['gsu_status']);
                    } else {
                        $values['gsu_status'] = new Zend_Db_Expr('NULL');
                        $messages[] = sprintf($this->translate->_('The status warning for the \'%s\' survey was removed.'), $survey->getName());
                    }
                }

                if ($survey->getName() != $surveyor_title) {
                    $values['gsu_survey_name'] = $surveyor_title;
                    $messages[] = sprintf($this->translate->_('The name of the \'%s\' survey has changed to \'%s\'.'), $survey->getName(), $surveyor_title);
                }

            } else { // New record
                $values['gsu_survey_name']        = $surveyor_title;
                $values['gsu_survey_description'] = strtr(substr($surveyor_survey['description'], 0, 100), "\xA0\xC2", '  ');
                $values['gsu_surveyor_active']    = $surveyor_active ? 1 : 0;
                $values['gsu_active']             = 0;
                $values['gsu_status']             = $surveyor_status;

                $messages[] = sprintf($this->translate->_('Imported the \'%s\' survey.'), $surveyor_title);
            }
            $survey->saveSurvey($values, $userId);
        }

        // TODO: check for token field in survey table.

        return $messages;
    }

    /**
     * Updates the consent code of the the token in the source (if needed)
     *
     * @param Gems_Tracker_Token $token
     * @param int $surveyId Gems Survey Id
     * @param string $sourceSurveyId Optional Survey Id used by source
     * @param string $consentCode Optional consent code, otherwise code from token is used.
     * @return int 1 of the token was inserted or changed, 0 otherwise
     */
    public function updateConsent(Gems_Tracker_Token $token, $surveyId, $sourceSurveyId = null, $consentCode = null)
    {
        if (null === $sourceSurveyId) {
            $sourceSurveyId = $this->_getSid($surveyId);
        }

        $lsDb     = $this->getSourceDatabase();
        $lsTokens = $this->_getTokenTableName($sourceSurveyId);
        $tokenId  = $this->_getToken($token->getTokenId());

        if (null === $consentCode) {
            $consentCode = (string) $token->getConsentCode();
        }
        $values['attribute_3'] = $consentCode;

        if ($oldValues = $lsDb->fetchRow("SELECT * FROM $lsTokens WHERE token = ? LIMIT 1", $tokenId)) {

            if ($this->tracker->filterChangesOnly($oldValues, $values)) {
                if (Gems_Tracker::$verbose) {
                    $echo = '';
                    foreach ($values as $key => $val) {
                        $echo .= $key . ': ' . $oldValues[$key] . ' => ' . $val . "\n";
                    }
                    MUtil_Echo::r($echo, "Updated limesurvey values for $tokenId");
                }

                $result = $lsDb->update($lsTokens, $values, array('token = ?' => $tokenId));

                if ($result) {
                    //If we have changed something, invalidate the cache
                    $token->cacheReset('tokenInfo');
                }
                return $result;
            }
        }

        return 0;
    }
}
