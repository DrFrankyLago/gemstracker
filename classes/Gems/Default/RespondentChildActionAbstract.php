<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentChildActionAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 5-mei-2015 13:15:49
 */
abstract class Gems_Default_RespondentChildActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_Tracker_Respondent
     */
    private $_respondent;

    /**
     * Model level parameters used for all actions, overruled by any values set in any other
     * parameters array except the private $_defaultParamters values in this module.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $defaultParameters = array(
        'multiTracks' => 'isMultiTracks',
        'respondent' => 'getRespondent',
    );

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $importParameters = array('respondent' => null);

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'AutosearchInRespondentSnippet');

    /**
     * Get the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    protected function getRespondent()
    {
        if (! $this->_respondent) {
            $patientNumber  = $this->_getParam(\MUtil_Model::REQUEST_ID1);
            $organizationId = $this->_getParam(\MUtil_Model::REQUEST_ID2);

            $this->_respondent = $this->loader->getRespondent($patientNumber, $organizationId);

            if (! $this->_respondent->exists && $patientNumber && $organizationId) {
                throw new \Gems_Exception($this->_('Unknown respondent.'));
            }

            $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $this->_respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        if ($this->_getParam(\MUtil_Model::REQUEST_ID1)) {
            return $this->getRespondent()->getId();
        }
    }

    /**
     *
     * @return boolean
     */
    protected function isMultiTracks()
    {
        return ! $this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface;
    }
}
