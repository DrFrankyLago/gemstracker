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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Standard Respondent model.
 *
 * When a project defines its own sub-class of this class and names
 * it <Project_name>_Model_RespondentModel, that class is loaded
 * instead.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_RespondentModel extends \Gems_Model_HiddenOrganizationModel
{
    /**
     * Store the SSN hashed in the database and display only '*****'
     */
    const SSN_HASH = 1;

    /**
     * Do not use the SSN
     */
    const SSN_HIDE = 2;

    /**
     * Store the SSN as is and display the value.
     */
    const SSN_OPEN = 4;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Determines the algorithm used to hash the social security number
     *
     * Can be changed is derived classes, set to null to use old md5() method
     *
     * @var int One of the SSN_ constants
     */
    public $hashAlgorithm = 'sha512';

    /**
     * Determines how the social security number is stored.
     *
     * Can be changed is derived classes.
     *
     * @var int One of the SSN_ constants
     */
    public $hashSsn = self::SSN_HASH;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should the logincheck be added automatically
     *
     * @var boolean
     */
    protected $loginCheck = false;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    public $view;

    /**
     * Self constructor
     */
    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('respondents', 'gems__respondents', 'grs');

        $this->addTable('gems__respondent2org', array('grs_id_user' => 'gr2o_id_user'), 'gr2o');
        $this->addTable('gems__reception_codes', array('gr2o_reception_code' => 'grc_id_reception_code'));

        $this->setKeys($this->_getKeysFor('gems__respondent2org'));

        $this->addColumn(new \Zend_Db_Expr("CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END"), 'row_class');
        $this->addColumn(new \Zend_Db_Expr("CASE WHEN grc_success = 1 THEN 0 ELSE 1 END"), 'resp_deleted');

        if (! $this->has('grs_ssn')) {
            $this->hashSsn = self::SSN_HIDE;
        }
        if (self::SSN_HASH === $this->hashSsn) {
            $this->setSaveWhen('grs_ssn', array($this, 'whenSSN'));
            $this->setOnLoad('grs_ssn', array($this, 'hideSSN'));
            $this->setOnSave('grs_ssn', array($this, 'saveSSN'));
        }
    }

    /**
     * Add an organization filter if it wasn't specified in the filter.
     *
     * Checks the filter on sematic correctness and replaces the text seacrh filter
     * with the real filter.
     *
     * @param mixed $filter True for the filter stored in this model or a filter array
     * @return array The filter to use
     */
    protected function _checkFilterUsed($filter)
    {
        $filter = parent::_checkFilterUsed($filter);

        if (isset($filter['gr2o_id_organization'])) {
            // Check for option to check for any organisation
            if (true === $filter['gr2o_id_organization']) {
                unset($filter['gr2o_id_organization']);
            }
        } else {
            // Add the correct filter
            if ($this->isMultiOrganization() && !isset($filter['gr2o_patient_nr'])) {
                $allowed = $this->currentUser->getAllowedOrganizations();

                // If we are not looking for a specific patient, we can look at all patients
                $filter['gr2o_id_organization'] = array_keys($allowed);
            } else {
                // Otherwise, we can only see in our current organization
                $filter['gr2o_id_organization'] = $this->getCurrentOrganization();
            }
        }

        if (self::SSN_HASH === $this->hashSsn) {
            // Make sure a search for a SSN is hashed when needed.
            array_walk_recursive($filter, array($this, 'applyHash'));
        }

        return $filter;
    }

    /**
     * Add the table and field to check for respondent login checks
     *
     * @return \Gems_Model_RespondentModel (continuation pattern)
     */
    public function addLoginCheck()
    {
        if (! $this->hasAlias('gems__user_logins')) {
            $this->addLeftTable(
                    'gems__user_logins',
                    array('gr2o_patient_nr' => 'gul_login', 'gr2o_id_organization' => 'gul_id_organization'),
                    'gul',
                    \MUtil_Model_DatabaseModelAbstract::SAVE_MODE_UPDATE |
                        \MUtil_Model_DatabaseModelAbstract::SAVE_MODE_DELETE);
        }

        $this->addColumn(
                "CASE WHEN gul_id_user IS NULL OR gul_user_class = 'NoLogin' OR gul_can_login = 0 THEN 0 ELSE 1 END",
                'has_login');

        return $this;
    }

    /**
     * Add the respondent name as a caclulated field to the model
     * @param \Gems_Model_JoinModel $model
     * @param string $label
     */
    public static function addNameToModel(\Gems_Model_JoinModel $model, $label)
    {
        $nameExpr[]  = "COALESCE(grs_last_name, '-')";
        $fieldList[] = 'grs_last_name';
        if ($model->has('grs_partner_last_name')) {
            if ($model->has('grs_partner_surname_prefix')) {
                $nameExpr[]  = "COALESCE(CONCAT(' ', grs_partner_surname_prefix), '')";
                $fieldList[] = 'grs_partner_surname_prefix';
            }

            $nameExpr[]  = "COALESCE(CONCAT(' ', grs_partner_last_name), '')";
            $fieldList[] = 'grs_partner_last_name';
        }
        $nameExpr[] = "', '";

        if ($model->has('grs_first_name')) {
            if ($model->has('grs_initials_name')) {
                $nameExpr[]  = "COALESCE(grs_first_name, grs_initials_name, '')";
                $fieldList[] = 'grs_first_name';
                $fieldList[] = 'grs_initials_name';
            } else {
                $nameExpr[]  = "COALESCE(grs_first_name, '')";
                $fieldList[] = 'grs_first_name';
            }
        } elseif ($model->has('grs_initials_name')) {
            $nameExpr[]  = "COALESCE(grs_initials_name, '')";
            $fieldList[] = 'grs_initials_name';
        }
        if ($model->has('grs_surname_prefix')) {
            $nameExpr[]  = "COALESCE(CONCAT(' ', grs_surname_prefix), '')";
            $fieldList[] = 'grs_surname_prefix';
        }
        $model->set('name',
                'label', $label,
                'column_expression', new \Zend_Db_Expr("CONCAT(" . implode(', ', $nameExpr) . ")"),
                'fieldlist', $fieldList);
   }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->setOnSave('gr2o_opened', new \MUtil_Db_Expr_CurrentTimestamp());
        $this->setSaveOnChange('gr2o_opened');
        $this->setOnSave('gr2o_opened_by', $this->currentUser->getUserId());
        $this->setSaveOnChange('gr2o_opened_by');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Model_RespondentModel
     */
    public function applyBrowseSettings()
    {
        $dbLookup   = $this->util->getDbLookup();
        $translated = $this->util->getTranslated();

        $this->resetOrder();

        if ($this->has('gr2o_id_organization') && $this->isMultiOrganization()) {
            // Add for sorting
            $this->addTable('gems__organizations', array('gr2o_id_organization' => 'gor_id_organization'));

            $this->setIfExists('gor_name', 'label', $this->translate->_('Organization'));

            $this->set('gr2o_id_organization',
                    'label', $this->_('Organization'),
                    'multiOptions', $dbLookup->getOrganizationsWithRespondents()
                    );
        }

        $this->setIfExists('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        self::addNameToModel($this, $this->_('Name'));

        $this->setIfExists('grs_email',       'label', $this->_('E-Mail'));
        $this->setIfExists('gr2o_mailable',   'label', $this->_('May be mailed'),
                'multiOptions', $translated->getYesNo()
                );

        $this->setIfExists('grs_address_1',   'label', $this->_('Street'));
        $this->setIfExists('grs_zipcode',     'label', $this->_('Zipcode'));
        $this->setIfExists('grs_city',        'label', $this->_('City'));

        $this->setIfExists('grs_phone_1',     'label', $this->_('Phone'));

        $this->setIfExists('grs_birthday',    'label', $this->_('Birthday'),
                'dateFormat', \Zend_Date::DATE_MEDIUM);

        $this->setIfExists('gr2o_opened',
                'label', $this->_('Opened'),
                'formatFunction', $translated->formatDateTime);
        $this->setIfExists('gr2o_consent',
                'label', $this->_('Consent'),
                'multiOptions', $dbLookup->getUserConsents()
                );

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Model_RespondentModel
     */
    public function applyDetailSettings()
    {
        $dbLookup   = $this->util->getDbLookup();
        $localized  = $this->util->getLocalized();
        $translated = $this->util->getTranslated();

        if ($this->loginCheck) {
            $this->addLoginCheck();
        }
        $this->resetOrder();
        if ($this->has('gr2o_id_organization')) {
            $this->set('gr2o_id_organization',
                    'label', $this->_('Organization'),
                    'tab', $this->_('Identification'),
                    'multiOptions', $this->currentUser->getRespondentOrganizations()
                    );

            $this->set('gr2o_id_organization', 'default', $this->currentUser->getCurrentOrganizationId());

            if (count($this->currentUser->getAllowedOrganizations()) == 1) {
                $this->set('gr2o_id_organization', 'elementClass', 'Exhibitor');
            }
        }

        // The SSN
        if ($this->hashSsn !== self::SSN_HIDE) {
            $this->set('grs_ssn', 'label', $this->_('SSN'),
                    'tab', $this->_('Identification'));
        }

        $this->setIfExists('gr2o_patient_nr', 'label', $this->_('Respondent number'),
                'tab', $this->_('Identification'));

        $this->setIfExists('grs_initials_name',  'label', $this->_('Initials'));
        $this->setIfExists('grs_first_name',  'label', $this->_('First name'));
        $this->setIfExists('grs_surname_prefix', 'label', $this->_('Surname prefix'),
                'description', $this->_('de, ibn, Le, Mac, von, etc...'));

        $this->setIfExists('grs_last_name',   'label', $this->_('Last name'));

        $this->setIfExists('grs_partner_surname_prefix', 'label', $this->_('Partner surname prefix'),
                'description', $this->_('de, ibn, Le, Mac, von, etc...'));
        $this->setIfExists('grs_partner_last_name',   'label', $this->_('Partner last name'));

        $this->setIfExists('grs_gender',      'label', $this->_('Gender'),
                'multiOptions', $translated->getGenderHello()
                );

        $this->setIfExists('grs_birthday',    'label', $this->_('Birthday'),
                'dateFormat', \Zend_Date::DATE_MEDIUM
                );

        $this->setIfExists('grs_email',       'label', $this->_('E-Mail'),
                'tab', $this->_('Contact information'));
        $this->setIfExists('gr2o_mailable',   'label', $this->_('May be mailed'),
                'elementClass', 'radio',
                'separator', ' ',
                'multiOptions', $translated->getYesNo()
                );

        $this->setIfExists('gr2o_treatment',  'label', $this->_('Treatment'));
        $this->setIfExists('gr2o_comments',   'label', $this->_('Comments'));

        $this->setIfExists('grs_address_1',   'label', $this->_('Street'));
        $this->setIfExists('grs_address_2',   'label', ' ');

        // \MUtil_Echo::track($this->getItemsOrdered(), $this->getOrder('grs_email'));

        $this->setIfExists('grs_zipcode',     'label', $this->_('Zipcode'));
        $this->setIfExists('grs_city',        'label', $this->_('City'));
        $this->setIfExists('grs_iso_country', 'label', $this->_('Country'),
                'multiOptions', $localized->getCountries());

        $this->setIfExists('grs_phone_1',     'label', $this->_('Phone'));
        $this->setIfExists('grs_phone_2',     'label', $this->_('Phone 2'));
        $this->setIfExists('grs_phone_3',     'label', $this->_('Phone 3'));
        $this->setIfExists('grs_phone_4',     'label', $this->_('Phone 4'));

        $this->setIfExists('grs_iso_lang',    'label', $this->_('Language'),
                'multiOptions', $localized->getLanguages(),
                'tab', $this->_('Settings'), 'default', $this->project->getLocaleDefault());

        $this->setIfExists('gr2o_consent',    'label', $this->_('Consent'),
                'default', $this->util->getDefaultConsent(),
                'description', $this->_('Has the respondent signed the informed consent letter?'),
                'multiOptions', $dbLookup->getUserConsents()
                );

        $changers = $this->getChangersList();

        $this->setIfExists('gr2o_opened',     'label', $this->_('Opened'),
                'dateFormat', \Zend_Date::DATE_MEDIUM,
                'default', '',
                'elementClass', 'None',  // Has little use to show: is usually editor
                'formatFunction', array($translated, 'formatDateTime')
                );
        $this->setIfExists('gr2o_opened_by',  'label', $this->_('Opened'),
                'elementClass', 'None',  // Has little use to show: is usually editor
                'multiOptions', $changers
                );
        $this->setIfExists('gr2o_changed',    'label', $this->_('Changed on'),
                'dateFormat', \Zend_Date::DATE_MEDIUM,
                'default', '',
                'formatFunction', array($translated, 'formatDateTime')
                );
        $this->setIfExists('gr2o_changed_by', 'label', $this->_('Changed by'),
                'multiOptions', $changers
                );
        $this->setIfExists('gr2o_created',    'label', $this->_('Creation date'),
                'dateFormat', \Zend_Date::DATE_MEDIUM,
                'default', '',
                'formatFunction', array($translated, 'formatDateTime')
                );
        $this->setIfExists('gr2o_created_by', 'label', $this->_('Creation by'),
                'multiOptions', $changers
                );

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param boolean $create True when creating
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings($create = false)
    {
        $this->applyDetailSettings();
        $this->copyKeys(); // The user can edit the keys.

        $translated = $this->util->getTranslated();
        $ucfirst    = new \Zend_Filter_Callback('ucfirst');

        if ($this->hashSsn !== self::SSN_HIDE) {
            $onblur = new \MUtil_Html_JavascriptArrayAttribute('onblur');
            $onblur->addSubmitOnChange('this.value');

            $this->set('grs_ssn',
                    'onblur', $onblur->render($this->view),  // Render needed as element does not know HtmlInterface
                    'validator[]', $this->createUniqueValidator('grs_ssn')
                    );
        }

        $this->set('gr2o_id_organization', 'default', $this->currentUser->getCurrentOrganizationId());
        $this->setIfExists('gr2o_patient_nr',
                'size', 15,
                'minlength', 4,
                'validator', $this->createUniqueValidator(
                        array('gr2o_patient_nr', 'gr2o_id_organization'),
                        array('gr2o_id_user' => 'grs_id_user', 'gr2o_id_organization')
                        )
                );
        $this->set('grs_id_user');

        $this->set('grs_email',
                'required', true,
                'autoInsertNotEmptyValidator', false, // Make sure it works ok with next
                'size', 30,
                'validator', 'SimpleEmail');
        $this->addColumn('CASE WHEN grs_email IS NULL OR LENGTH(TRIM(grs_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');
        $this->set('calc_email',
                'label', $this->_('Respondent has no e-mail'),
                'elementClass', 'Checkbox',
                'required', true,
                'order', $this->getOrder('grs_email') + 1,
                'validator', new \Gems_Validate_OneOf(
                        $this->_('Respondent has no e-mail'),
                        'grs_email',
                        $this->get('grs_email', 'label')
                        )
                );
        $this->set('gr2o_mailable',
                'label', $this->_('May be mailed'),
                'elementClass', 'radio',
                'multiOptions', $translated->getYesNo(),
                'separator', ' '
                );

        $this->setIfExists('grs_first_name', 'filter', $ucfirst);
        $this->setIfExists('grs_last_name',  'filter', $ucfirst, 'required', true);
        $this->setIfExists('grs_partner_last_name',  'filter', new \Zend_Filter_Callback('ucfirst'));

        $this->setIfExists('grs_gender',
                'elementClass', 'Radio',
                'separator', '',
                'multiOptions', $translated->getGenders(),
                'tab', $this->_('Medical data')
                );

        $this->setIfExists('grs_birthday',
                'jQueryParams', array('defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => 'c-130:c0'),
                'elementClass', 'Date',
                'validator', new \MUtil_Validate_Date_DateBefore()
                );

        $this->setIfExists('gr2o_treatment', 'size', 30);
        $this->setIfExists('gr2o_comments',  'elementClass', 'Textarea', 'rows', 4, 'cols', 60);

        $this->setIfExists('grs_address_1',
                'size',  40,
                'description', $this->_('With housenumber'),
                'filter', $ucfirst
                );
        $this->setIfExists('grs_address_2', 'size', 40);
        $this->setIfExists('grs_city', 'filter', $ucfirst);
        $this->setIfExists('grs_phone_1', 'size', 15);
        $this->setIfExists('grs_phone_2', 'size', 15);
        $this->setIfExists('grs_phone_3', 'size', 15);
        $this->setIfExists('grs_phone_4', 'size', 15);

        $this->setIfExists('gr2o_consent',
                'default', $this->util->getDefaultConsent(),
                'elementClass', 'Radio',
                'separator', ' ',
                'required', true);

        $this->setMulti(array('name', 'row_class', 'resp_deleted'), 'elementClass', 'None');
        $this->setMulti($this->getItemsFor('table', 'gems__reception_codes'), 'elementClass', 'None');

        if ($create) {
            $this->setIfExists('gr2o_changed',    'elementClass', 'None');
            $this->setIfExists('gr2o_changed_by', 'elementClass', 'None');
            $this->setIfExists('gr2o_created',    'elementClass', 'None');
            $this->setIfExists('gr2o_created_by', 'elementClass', 'None');
        } else {
            $this->setIfExists('gr2o_changed',    'elementClass', 'Exhibitor');
            $this->setIfExists('gr2o_changed_by', 'elementClass', 'Exhibitor');
            $this->setIfExists('gr2o_created',    'elementClass', 'Exhibitor');
            $this->setIfExists('gr2o_created_by', 'elementClass', 'Exhibitor');
        }

        return $this;
    }

    /**
     * Apply hash function for array_walk_recursive in _checkFilterUsed()
     *
     * @see _checkFilterUsed()
     *
     * @param string $filterValue
     * @param string $filterKey
     */
    public function applyHash(&$filterValue, $filterKey)
    {
        if ('grs_ssn' === $filterKey) {
            $filterValue = $this->project->getValueHash($filterValue, $this->hashAlgorithm);
        }
    }

    public function copyKeys($reset = false)
    {
        $keys = $this->_getKeysFor('gems__respondent2org');

        foreach ($keys as $key) {
            $this->addColumn('gems__respondent2org.' . $key, $this->getKeyCopyName($key));
        }

        return $this;
    }

    /**
     * Count the number of tracks the respondent has for this organization
     *
     * @param string $patientId   Can be empty if $respondentId is passed
     * @param int $organizationId When null looks at all organizations
     * @param int $respondentId   Pass when at hand, is looked up otherwise
     * @param boolean $active     When true only tracks with a success code are returned
     * @return boolean
     */
    public function countTracks($patientId, $organizationId, $respondentId = null, $active = false)
    {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from('gems__respondent2track', array('COALESCE(COUNT(*), 0)'));

        if (null === $respondentId) {
            $respondentId = $this->util->getDbLookup()->getRespondentId($patientId, $organizationId);
        }

        $select->where('gr2t_id_user = ?', $respondentId);

        if (null !== $organizationId) {
            $select->where('gr2t_id_organization = ?', $organizationId);
        }

        if ($active) {
            $select->joinInner('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code')
                    ->where('grc_success = 1');
        }

        return $db->fetchOne($select);
    }

    /**
     * Return a list of those who could be on the created_by, changed_by or opened_by fields
     *
     * @return array id => name
     */
    protected function getChangersList()
    {
        return $this->util->getDbLookup()->getStaff();
    }

    /**
     * Get the current reception code for a respondent
     *
     * @param string $patientId Can be empty if $respondentId is passed
     * @param int $organizationId
     * @param int $respondentId   Pass when at hand, is looked up otherwise
     * @return string The current reception code
     */
    public function getReceptionCode($patientId, $organizationId, $respondentId = null)
    {
        $db     = $this->getAdapter();
        $select = $db->select();
        $select->from('gems__respondent2org', array('gr2o_reception_code'));

        if ($patientId) {
            $select->where('gr2o_patient_nr = ?', $patientId);
        } else {
            $select->where('gr2o_id_user = ?', $respondentId);
        }

        $select->where('gr2o_id_organization = ?', $organizationId);

        return $db->fetchOne($select);
    }

    /**
     * Create a database model for retrieving the respondent tracks
     *
     * @return \Gems_Model_JoinModel A NEW JoinModel, not a continuation pattern return
     */
    public function getRespondentTracksModel()
    {
        $model = new \Gems_Model_JoinModel('surveys', 'gems__respondent2track');
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__respondent2org', array('gr2t_id_user' => 'gr2o_id_user'));

        return $model;
    }

    /**
     * Has the respondent tracks
     *
     * @param string $patientId   Can be empty if $respondentId is passed
     * @param int $organizationId When null looks at all organizations
     * @param int $respondentId   Pass when at hand, is looked up otherwise
     * @param boolean $active     When true we look only for tracks with a success code
     * @return boolean
     */
    public function hasTracks($patientId, $organizationId, $respondentId = null, $active = false)
    {
        return (boolean) $this->countTracks($patientId, $organizationId, $respondentId, $active);
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The output to display
     */
    public function hideSSN($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        if ($value && (! $isPost)) {
            return str_repeat('*', 9);
        } else {
            return $value;
        }
    }

    /**
     * True when the default filter can contain multiple organizations
     *
     * @return boolean
     */
    public function isMultiOrganization()
    {
        return $this->currentUser->hasPrivilege('pr.respondent.multiorg');
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        // If the respondent id is not set, check using the
        // patient number and then the ssn
        if (! (isset($newValues['grs_id_user']) && $newValues['grs_id_user'])) {
            $id = false;

            if (isset($newValues['gr2o_patient_nr'], $newValues['gr2o_id_organization'])) {
                $sql = 'SELECT gr2o_id_user
                        FROM gems__respondent2org
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

                $id = $this->db->fetchOne($sql, array($newValues['gr2o_patient_nr'], $newValues['gr2o_id_organization']));
            }

            if ((!$id) &&
                    isset($newValues['grs_ssn']) &&
                    ($this->hashSsn !== self::SSN_HIDE)) {

                if (self::SSN_HASH === $this->hashSsn) {
                    $search = $this->saveSSN($newValues['grs_ssn']);
                } else {
                    $search = $newValues['grs_ssn'];
                }

                $sql = 'SELECT grs_id_user FROM gems__respondents WHERE grs_ssn = ?';

                $id = $this->db->fetchOne($sql, $search);

                // Check for change in patient ID
                if ($id && isset($newValues['gr2o_id_organization'])) {

                    $sql = 'SELECT gr2o_patient_nr
                            FROM gems__respondent2org
                            WHERE gr2o_id_user = ? AND gr2o_id_organization = ?';

                    $patientId = $this->db->fetchOne($sql, array($id, $newValues['gr2o_id_organization']));

                    if ($patientId) {
                        $copyId             = $this->getKeyCopyName('gr2o_patient_nr');
                        $newValues[$copyId] = $patientId;
                    }
                }
            }

            if ($id) {
                $newValues['grs_id_user']  = $id;
                $newValues['gr2o_id_user'] = $id;
            }
            // If empty, then set by \Gems_Model->createGemsUserId()
        }

        $result = parent::save($newValues, $filter, $saveTables);

        if (isset($result['gr2o_id_organization']) && isset($result['grs_id_user'])) {
            // Tell the organization it has at least one user
            $org = $this->loader->getOrganization($result['gr2o_id_organization']);
            if ($org) {
                $org->setHasRespondents($this->currentUser->getUserId());
            }
        }

        return $result;
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function saveSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            return $this->project->getValueHash($value, $this->hashAlgorithm);
        }
    }

    /**
     * Set the reception code for a respondent and cascade non-success codes to the
     * tracks / surveys.
     *
     * @param string $patientId   Can be empty if $respondentId is passed
     * @param int $organizationId
     * @param string $newCode     String or \Gems_Util_ReceptionCode
     * @param int $respondentId   Pass when at hand, is looked up otherwise
     * @param string $oldCode     Pass when at hand as tring or \Gems_Util_ReceptionCode, is looked up otherwise
     * @return \Gems_Util_ReceptionCode The new code reception code object for further processing
     */
    public function setReceptionCode($patientId, $organizationId, $newCode, $respondentId = null, $oldCode = null)
    {
        if ($newCode instanceof \Gems_Util_ReceptionCode) {
            $code    = $newCode;
            $newCode = $code->getCode();
        } else {
            $code    = $this->util->getReceptionCode($newCode);
        }
        $userId = $this->currentUser->getUserId();

        // Perform actual save, but not for simple stop codes.
        if ($code->isForRespondents()) {
            if (null === $oldCode) {
                $oldCode = $this->getReceptionCode($patientId, $organizationId, $respondentId);
            }
            if ($oldCode instanceof \Gems_Util_ReceptionCode) {
                $oldCode = $oldCode->getCode();
            }

            // If the code wasn't set already
            if ($oldCode !== $newCode) {
                $values['gr2o_reception_code'] = $newCode;
                $values['gr2o_changed']        = new \MUtil_Db_Expr_CurrentTimestamp();
                $values['gr2o_changed_by']     = $userId;

                if ($patientId) {
                    // Update though primamry key is prefered
                    $where = 'gr2o_patient_nr = ? AND gr2o_id_organization = ?';
                    $where = $this->db->quoteInto($where, $patientId, null, 1);
                } else {
                    $where = 'gr2o_id_user = ? AND gr2o_id_organization = ?';
                    $where = $this->db->quoteInto($where, $respondentId, null, 1);
                }
                $where = $this->db->quoteInto($where, $organizationId, null, 1);

                $this->db->update('gems__respondent2org', $values, $where);
            }
        }

        // Is the respondent really removed
        if (! $code->isSuccess()) {
            // Only check for $respondentId when it is really needed
            if (null === $respondentId) {
                $respondentId = $this->util->getDbLookup()->getRespondentId($patientId, $organizationId);
            }

            // Cascade to tracks
            // the responsiblilty to handle it correctly is on the sub objects now.
            $tracks = $this->loader->getTracker()->getRespondentTracks($respondentId, $organizationId);
            foreach ($tracks as $track) {
                if ($track->setReceptionCode($code, null, $userId)) {
                    $this->addChanged();
                }
            }
        }

        return $code;
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function whenSSN($value, $isNew = false, $name = null, array $context = array())
    {
        return $value && ($value !== $this->hideSSN($value, $isNew, $name, $context));
    }
}

