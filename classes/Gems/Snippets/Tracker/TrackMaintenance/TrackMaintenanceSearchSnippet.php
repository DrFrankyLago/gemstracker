<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrackMaintenanceSearchSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker\TrackMaintenance;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:10:55
 */
class TrackMaintenanceSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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

        if ($elements) {
            $br = \MUtil_Html::create('br');
            $elements[] = $this->_createSelectElement('gtr_track_class', $this->model, $this->_('(all track engines)'));

            $elements[] = $br;

            $optionsA = $this->util->getTranslated()->getYesNo();
            $elementA = $this->_createSelectElement('gtr_active', $optionsA, $this->_('(both)'));
            $elementA->setLabel($this->model->get('gtr_active', 'label'));
            $elements[] = $elementA;

            $user     = $this->loader->getCurrentUser();
            $optionsO = $user->getRespondentOrganizations();
            $elementO = $this->_createSelectElement('org', $optionsO, $this->_('(all organizations)'));
            $elements[] = $elementO;
        }

        return $elements;
    }
}
