<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Summary_SummarySearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
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
        $elements[] = $this->_createSelectElement(
                'gto_id_track',
                $this->util->getTrackData()->getAllTracks(),
                $this->_('(select a track)')
                );

        $orgs = $this->loader->getCurrentUser()->getRespondentOrganizations();
        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gto_id_organization', $orgs, $this->_('(all organizations)'));
        }

        $elements[] = null;

        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
            'gto_valid_from'  => $this->_('Valid from'),
            'gto_valid_until' => $this->_('Valid until'),
            );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');

        $elements[] = null;
        if (isset($data['gto_id_track']) && !empty($data['gto_id_track'])) {
            $trackId = $data['gto_id_track'];
        } else {
            $trackId = -1;
        }
        $sql = "SELECT ggp_name, ggp_name as label FROM (
                    SELECT DISTINCT ggp_name
                        FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                            INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                            INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                        WHERE ggp_group_active = 1 AND
                            gro_active=1 AND
                            gtr_active=1 AND
                            gtr_id_track = $trackId

                UNION ALL

                    SELECT DISTINCT gtf_field_name as ggp_name
                        FROM gems__track_fields
                        WHERE gtf_field_type = 'relation' AND
                            gtf_id_track = $trackId
                ) AS tmpTable
                ORDER BY ggp_name";
        $elements[] = $this->_createSelectElement('ggp_name', $sql, $this->_('(all fillers)'));

        return $elements;
    }
}