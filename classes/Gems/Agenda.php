<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Afenda.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Agenda extends MUtil_Translate_TranslateableAbstract
{
    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Get all active respondents for this user
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @return array appointmentId => appointment description
     */
    public function getActiveAppointments($respondentId, $organizationId, $patientNr = null)
    {
        $where = sprintf('gap_status IN (%s)', $this->getStatusKeysActiveDbQuoted());

        return $this->getAppointments($respondentId, $organizationId, $patientNr, $where);
    }

    /**
     * Get the select statement for appointments in getAppointments()
     *
     * Allows for overruling on project level
     *
     * @return Zend_Db_Select
     */
    protected function _getAppointmentSelect()
    {
        $select = $this->db->select();

        $select->from('gems__appointments')
                ->joinLeft( 'gems__agenda_activities', 'gap_id_activity = gaa_id_activity')
                ->joinLeft('gems__agenda_procedures',  'gap_id_procedure = gapr_id_procedure')
                ->joinLeft('gems__locations',          'gap_id_location = glo_id_location')
                ->order('gap_admission_time DESC');

        return $select;
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getActivities($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_activities', array('gaa_id_activity', 'gaa_name'))
                ->order('gaa_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gaa_active = 1')
                    ->where('(
                            gaa_id_organization IS NULL
                        AND
                            gaa_name NOT IN (SELECT gaa_name FROM gems__agenda_activities WHERE gaa_id_organization = ?)
                        ) OR
                            gaa_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('activities'));
        return $results;

    }

    /**
     * Overrule this function to adapt the display of the agenda items for each project
     *
     * @param array $row Row containing result select
     * @return string
     */
    public function getAppointmentDisplay(array $row)
    {
        $date = new MUtil_Date($row['gap_admission_time'], 'yyyy-MM-dd HH:mm:ss');
        $results[] = $date->toString('dd-MM-yyyy HH:mm');
        if ($row['gaa_name']) {
            $results[] = $row['gaa_name'];
        }
        if ($row['gapr_name']) {
            $results[] = $row['gapr_name'];
        }
        if ($row['glo_name']) {
            $results[] = $row['glo_name'];
        }

        return implode($this->_('; '), $results);
    }

    /**
     * Get all appointments for a respondent
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param string $where Optional extra where statement
     * @return array appointmentId => appointment description
     */
    public function getAppointments($respondentId, $organizationId, $patientNr = null, $where = null)
    {
        $select = $this->_getAppointmentSelect();

        if ($where) {
            $select->where($where);
        }

        if ($respondentId) {
            $select->where('gap_id_user = ?', $respondentId)
                    ->where('gap_id_organization = ?', $organizationId);
        } else {
            // Join might have been created in _getAppointmentSelect
            $from = $select->getPart(Zend_Db_Select::FROM);
            if (! isset($from['gems__respondent2org'])) {
                $select->joinInner(
                        'gems__respondent2org',
                        'gap_id_user = gr2o_id_user AND gap_id_organization = gr2o_id_organization',
                        array()
                        );
            }

            $select->where('gr2o_patient_nr = ?', $patientNr)
                    ->where('gr2o_id_organization = ?', $organizationId);
        }

        // MUtil_Echo::track($select->__toString());
        $rows = $this->db->fetchAll($select);

        if (! $rows) {
            return array();
        }

        $results = array();
        foreach ($rows as $row) {
            $results[$row['gap_id_appointment']] = $this->getAppointmentDisplay($row);
        }
        return $results;
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getHealthcareStaff($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_staff', array('gas_id_staff', 'gas_name'))
                ->order('gas_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gas_active = 1')
                    ->where('gas_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('staff'));
        return $results;

    }

    /**
     * Returns an array with identical key => value pairs containing care provision locations.
     *
     * @param int $orgId Optional to slect for single organization
     * @return array
     */
    public function getLocations($orgId = null)
    {
        // Make sure no invalid data gets through
        $orgId = intval($orgId);

        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $orgId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__locations', array('glo_id_location', 'glo_name'))
                ->order('glo_name');

        if ($orgId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('glo_active = 1');
            $select->where("glo_organizations LIKE '%:$orgId:%'");
        }

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('locations'));
        return $results;
    }


    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getProcedures($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_procedures', array('gapr_id_procedure', 'gapr_name'))
                ->order('gapr_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gapr_active = 1')
                    ->where('(
                            gapr_id_organization IS NULL
                        AND
                            gapr_name NOT IN (SELECT gapr_name FROM gems__agenda_procedures WHERE gapr_id_procedure = ?)
                        ) OR
                            gapr_id_organization = ?', $organizationId);
        }
        // MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('procedures'));
        return $results;
    }

    /**
     * Get the status codes for all active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodes()
    {
        $codes = $this->getStatusCodesActive() +
                $this->getStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesActive()
    {
        $codes = array(
            'AC' => $this->_('Active appointment'),
            'CO' => $this->_('Completed appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesInactive()
    {
        $codes = array(
            'AB' => $this->_('Aborted appointment'),
            'CA' => $this->_('Cancelled appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status keys for active agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysActive()
    {
        return array_keys($this->getStatusCodesActive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return Zend_Db_Expr
     */
    public function getStatusKeysActiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusCodesActive() as $key => $label) {
            $codes[] = $this->db->quote($key);
        }
        return new Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the status keys for inactive agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysInactive()
    {
        return array_keys($this->getStatusCodesInactive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return Zend_Db_Expr
     */
    public function getStatusKeysInactiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusCodesInactive() as $key => $label) {
            $codes[] = $this->db->quote($key);
        }
        return new Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the type codes for agenda items
     *
     * @return array code => label
     */
    public function getTypeCodes()
    {
        return array(
            'A' => $this->_('Ambulatory'),
            'E' => $this->_('Emergency'),
            'F' => $this->_('Field'),
            'H' => $this->_('Home'),
            'I' => $this->_('Inpatient'),
            'S' => $this->_('Short stay'),
            'V' => $this->_('Virtual'),
        );
    }

    /**
     * Find an activity code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchActivity($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_activities', array('gaa_id_activity', 'gaa_match_to', 'gaa_id_organization'));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gaa_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gaa_id_organization'];
                }
                foreach (explode('|', $row['gaa_match_to']) as $match) {
                    $matches[$match][$key] = $row['gaa_id_activity'];
                }
            }
            $this->cache->save($matches, $cacheId, array('activities'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_activities');
        Gems_Model::setChangeFieldsByPrefix($model, 'gaa');

        $values = array(
            'gaa_name'            => $name,
            'gaa_id_organization' => $organizationId,
            'gaa_match_to'        => $name,
            'gaa_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('activity', 'activities'));

        return $result['gaa_id_activity'];
    }


    /**
     * Find a healt care provider for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int gas_id_staff staff id
     */
    public function matchHealthcareStaff($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__agenda_staff')
                    ->order('gas_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['gas_match_to']) as $match) {
                    $matches[$match][$row['gas_id_organization']] = $row['gas_id_staff'];
                }
            }
            $this->cache->save($matches, $cacheId, array('staff'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                }
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_staff');
        Gems_Model::setChangeFieldsByPrefix($model, 'gas');

        $values = array(
            'gas_name'            => $name,
            'gas_id_organization' => $organizationId,
            'gas_match_to'        => $name,
            'gas_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('staff'));

        return $result['gas_id_staff'];
    }

    /**
     * Find a location for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return array location
     */
    public function matchLocation($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__locations')
                    ->order('glo_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['glo_match_to']) as $match) {
                    foreach (explode(':', trim($row['glo_organizations'], ':')) as $subOrg) {
                        $matches[$match][$subOrg] = $row;
                    }
                }
            }
            $this->cache->save($matches, $cacheId, array('locations'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                } else {
                    $first = reset($matches[$name]);

                    // Change this match, add this organization
                    $values = array(
                        'glo_id_location'   => $first['glo_id_location'],
                        'glo_organizations' => ':' . implode(':', array_keys($matches[$name])) . ':' .
                            $organizationId . ':',
                    );
                }
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        } else {
            // A new match
            $values = array(
                'glo_name'          => $name,
                'glo_organizations' => ':' . $organizationId . ':',
                'glo_match_to'      => $name,
                'glo_active'        => 1,
            );
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__locations');
        Gems_Model::setChangeFieldsByPrefix($model, 'glo');

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('location', 'locations'));

        return $result;
    }

    /**
     * Find a procedure code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchProcedure($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_procedures', array('gapr_id_procedure', 'gapr_match_to', 'gapr_id_organization'));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gapr_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gapr_id_organization'];
                }
                foreach (explode('|', $row['gapr_match_to']) as $match) {
                    $matches[$match][$key] = $row['gapr_id_procedure'];
                }
            }
            $this->cache->save($matches, $cacheId, array('procedures'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $model = new MUtil_Model_TableModel('gems__agenda_procedures');
        Gems_Model::setChangeFieldsByPrefix($model, 'gapr');

        $values = array(
            'gapr_name'            => $name,
            'gapr_id_organization' => $organizationId,
            'gapr_match_to'        => $name,
            'gapr_active'          => 1,
        );

        $result = $model->save($values);

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('procedure', 'procedures'));

        return $result['gapr_id_procedure'];
    }
}
