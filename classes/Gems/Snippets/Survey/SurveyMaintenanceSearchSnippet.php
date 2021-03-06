<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: SurveyMaintenanceSearchSnippet.php 2532 2015-04-30 16:33:05Z matijsdejong $
 */

namespace Gems\Snippets\Survey;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Survey
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 20-feb-2015 13:22:48
 */
class SurveyMaintenanceSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $groups     = $this->util->getDbLookup()->getGroups();
        $elements[] = $this->_createSelectElement('gsu_id_primary_group', $groups, $this->_('(all groups)'));

        $states     = array(
            'act' => $this->_('Active'),
            'sok' => $this->_('OK in source, not active'),
            'nok' => $this->_('Blocked in source'),
        );
        $elements[] = $this->_createSelectElement('status', $states, $this->_('(every state)'));

        $elements[] = \MUtil_Html::create('br');

        $yesNo      = $this->util->getTranslated()->getYesNo();
        $elements[] = $this->_createSelectElement('gsu_insertable', $yesNo, $this->_('(any insertable)'));

        return $elements;
    }
}
