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
class Gems_Snippets_Tracker_Compliance_ComplianceTableSnippet extends \Gems_Snippets_ModelTableSnippetGeneric
{
    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuEditActions = array();

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuShowActions = array('track' => 'show-track');

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $tUtil = $this->util->getTokenData();
        $table = $bridge->getTable();
        $table->appendAttrib('class', 'compliance');

        $thead  = $table->thead();
        $th_row = $thead->tr(array('class' => 'rounds'));
        $th     = $th_row->td();
        $span   = 1;
        $cRound = null;
        $cDesc  = null;
        $thead->tr();

        if ($showMenuItem = $this->getShowMenuItem()) {
            $bridge->addItemLink($showMenuItem->toActionLinkLower($this->request, $bridge));
        }

        // Initialize alter
        $alternateClass = new \MUtil_Lazy_Alternate(array('odd', 'even'));

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $round = $model->get($name, 'round');
                if ($round == $cRound) {
                    $span++;
                    $class = null;
                } else {
                    // If the round has an icon, show the icon else just 'R' since
                    // complete round description messes up the display
                    $th->append($cDesc);
                    $th->title = $cRound;
                    $th->colspan = $span;

                    $span    = 1;
                    $cRound  = $round;
                    if ($cIcon = $model->get($name, 'roundIcon')) {
                        $cDesc = \MUtil_Html_ImgElement::imgFile($cIcon, array(
                            'alt'   => $cRound,
                            'title' => $cRound
                        ));
                    } else {
                        if (substr($name, 0, 5) == 'stat_') {
                            $cDesc = 'R';
                        } else {
                            $cDesc = null;
                        }
                    }
                    $class   = 'newRound';
                    $thClass = $class .' ' . $alternateClass; // Add alternate class only for th
                    $th      = $th_row->td(array('class' => $thClass));
                }

                if ($model->get($name, 'noSort')) {
                    $title = array(
                        \MUtil_Lazy::method($tUtil, 'getStatusDescription', $bridge->$name),
                        "\n" . $model->get($name, 'description')
                        );
                    $token = 'tok_' . substr($name, 5);

                    $href = new \MUtil_Html_HrefArrayAttribute(array(
                        $this->request->getControllerKey() => 'track', // This code is only used for tracks :)
                        $this->request->getActionKey()     => 'show',
                        \MUtil_Model::REQUEST_ID            => $bridge->$token,
                        ));
                    $href->setRouteReset();

                    $onclick = new \MUtil_Html_OnClickArrayAttribute();
                    $onclick->addUrl($href)
                            ->addCancelBubble();

                    $tds   = $bridge->addColumn(
                            array(
                                \MUtil_Html_AElement::iflink(
                                        $bridge->$token,
                                        array(
                                            $href,
                                            'onclick' => 'event.cancelBubble = true;',
                                            'title' => $title,
                                            $bridge->$name,
                                            ),
                                        $bridge->$name
                                        ),
                                'class'   => array('round', \MUtil_Lazy::method($tUtil, 'getStatusClass', $bridge->$name)),
                                'title'   => $title,
                                // onclick is needed because the link does not fill the whole cell
                                'onclick' => \MUtil_Lazy::iff($bridge->$token, $onclick),
                                ),
                            array($label, 'title' => $model->get($name, 'description'), 'class' => 'round')
                            );
                } else {
                    $tds = $bridge->addSortable($name, $label);
                }
                if ($class) {
                    $tds->appendAttrib('class', $class);
                }
            }
        }
        $th->append($cRound);
        $th->colspan = $span;
    }
}
