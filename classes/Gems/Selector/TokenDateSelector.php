<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Selector_TokenDateSelector extends \Gems_Selector_DateSelectorAbstract
{
    /**
     * The name of the database table to use as the main table.
     *
     * @var string
     */
    protected $dataTableName = 'gems__tokens';

    /**
     * The name of the field where the date is calculated from
     *
     * @var string
     */
    protected $dateFrom = 'gto_valid_from';

    /**
     * The base filter to search with
     *
     * @var array
     */
    protected $searchFilter;

    /**
     *
     * @param string $name
     * @return \Gems_Selector_SelectorField
     */
    public function addSubField($name)
    {
        $field = $this->addField($name);
        $field->setClass('smallTime');
        $field->setLabelClass('indentLeft smallTime');

        return $field;
    }

    /**
     * Tells the models which fields to expect.
     */
    protected function loadFields()
    {
        $forResp  = $this->_('for respondents');
        $forStaff = $this->_('for staff');

        $this->addField('tokens')
                ->setLabel($this->_('Activated surveys'))
                ->setToCount("gto_id_token");

        $this->addField('todo')
                ->setLabel($this->_('Unanswered surveys'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 0");

        $this->addField('going')
                ->setLabel($this->_('Partially completed'))
                ->setToSumWhen("gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gto_in_source = 1");
        /*
        $this->addField('adleft')
                ->setLabel($this->_('Time left in days - average'))
                ->setToAverage("CASE WHEN gto_valid_until IS NOT NULL AND gto_valid_until >= CURRENT_TIMESTAMP THEN DATEDIFF(gto_valid_until, CURRENT_TIMESTAMP) ELSE NULL END", 2);
        // */

        $this->addField('missed')
                ->setLabel($this->_('Expired surveys'))
                ->setToSumWhen("gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP");

        $this->addField('done')
                ->setLabel($this->_('Answered surveys'))
                ->setToSumWhen("gto_completion_time IS NOT NULL");
        /*
        $this->addField('adur')
                ->setLabel($this->_('Answer time in days - average'))
                ->setToAverage("gto_completion_time - gto_valid_from", 2);
        // */
    }

    /**
     * Processing of filter, can be overriden.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param array $filter
     * @return array
     */
    protected function processFilter(\Zend_Controller_Request_Abstract $request, array $filter)
    {
        // \MUtil_Echo::r($filter, __CLASS__ . '->' . __FUNCTION__);

        $mainFilter = isset($filter['main_filter']) ? $filter['main_filter'] : null;
        unset($filter['main_filter']);

        $output = parent::processFilter($request, $filter);

        if ($mainFilter) {
            $output['main_filter'] = $mainFilter;
        }

        return $output;
    }

    /**
     * Stub function to allow extension of standard one table select.
     *
     * @param \Zend_Db_Select $select
     */
    protected function processSelect(\Zend_Db_Select $select)
    {
        // $select->joinLeft('gems__rounds',      'gto_id_round = gro_id_round', array());
        // $select->join('gems__tracks',          'gto_id_track = gtr_id_track', array());
        $select->join('gems__surveys',         'gto_id_survey = gsu_id_survey', array());
        $select->join('gems__groups',          'gsu_id_primary_group = ggp_id_group', array());
        $select->join('gems__respondents',     'gto_id_respondent = grs_id_user', array());
        $select->join('gems__respondent2track','gto_id_respondent_track = gr2t_id_respondent_track', array());
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', array());
    }

    protected function setTableBody(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Lazy_RepeatableInterface $repeater, $columnClass)
    {
        // $bridge->setAlternateRowClass('even', 'even', 'odd');

        parent::setTableBody($bridge, $repeater, $columnClass);
    }
}
