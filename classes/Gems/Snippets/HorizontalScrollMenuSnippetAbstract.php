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
 * @subpackage Snippets
 * @author     Jasper van Gestel <jvangestl@gmail.com>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: HorizontalScrollMenuSnippetAbstract.php.php 1323 2013-07-16 17:39:34Z matijsdejong $
 */

/**
 * Abstract class for quickly creating a tabbed bar, or rather a div that contains a number
 * of links, adding specific classes for display.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
//abstract class MUtil_Snippets_TabSnippetAbstract extends MUtil_Snippets_SnippetAbstract
abstract class Gems_Snippets_HorizontalScrollMenuSnippetAbstract extends MUtil_Snippets_SnippetAbstract
{
    /**
     * Optional standard url parts
     *
     * @var array
     */
    protected $baseUrl = array();

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'horizontal_scroll_menu';

    /**
     *
     * @var string Id of the current tab
     */
    protected $currentTab;

    /**
     *
     * @var string Id of default tab
     */
    protected $defaultTab;

    /**
     * Show bar when there is only a single tab
     *
     * @var boolean
     */
    protected $displaySingleTab = false;

    /**
     * Default href parameter values
     *
     * @var array
     */
    protected $href = array();


    /**
     *
     * @var string Label of the next button
     */

    protected $nextLabel = '>';


    /**
     *
     * @var string Label of the previous button
     */

    protected $prevLabel = '<';


    /**
     * Optional: $request or $tokenData must be set
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var int Show scroll controls from this number of tabs
     */
    protected $scrollFromSize = 4;

    /**
     *
     * @var string Class attribute for active tab
     */
    protected $tabActiveClass = 'active';

    /**
     *
     * @var string Class attribute for all tabs
     */
    protected $tabClass       = 'tab';


    /**
     *
     * @var int Length of a label before it is cut off
     */

    protected $tabLabelLength = 20;


    /**
     *
     * @var string String that is added to a cut off label
     */
    protected $tabLabelCutOffString = '...';


    protected $view;

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        return null;
    }

    /**
     * Return the parameters that should be used for this tabId
     *
     * @param string $tabId
     * @return array
     */
    protected function getParameterKeysFor($tabId)
    {
        $paramKey = $this->getParameterKey();

        if ($paramKey) {
            if ($tabId == $this->defaultTab) {
                return array($paramKey => null);
            } else {
                return array($paramKey => $tabId);
            }
        }

        return array();
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    abstract protected function getTabs();

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $tabs = $this->getTabs();

        $tabCount = count($tabs);

        if ($tabs && ($this->displaySingleTab || $tabCount > 1)) {


            // Is there a better helper to include JS?

            $view->headScript()->appendFile(Zend_Controller_Front::getInstance()->getBaseUrl()  .  '/gems/js/jquery.horizontalScrollMenu.js');

            $script = '(function($) {$(".'.$this->class.'").horizontalScrollMenu();}(jQuery));';

            $view->inlineScript()->appendScript($script);

            // When empty, first is default
            if (null === $this->defaultTab) {
                reset($tabs);
                $this->defaultTab = key($tabs);
            }
            if (null === $this->currentTab) {
                $this->currentTab = $this->request->getParam($this->getParameterKey());

                // Param can exist and be empty
                if (! $this->currentTab) {
                    $this->currentTab = $this->defaultTab;
                }
            }

            $scrollContainer = MUtil_Html::create()->div();

            if ($tabCount > $this->scrollFromSize) {
                $scrollContainer->a('#', $this->prevLabel, array('class' => 'prev'));
            }

            $tabRow = $scrollContainer->div(array('class' => 'container'))->ul();

            foreach ($tabs as $tabId => $content) {
                $li = $tabRow->li(array('class' => $this->tabClass));

                if (strlen($content) > $this->tabLabelLength) {
                    $content = substr($content,0, $this->tabLabelLength) .$this->tabLabelCutOffString;

                }

                $li->a($this->getParameterKeysFor($tabId) + $this->href, $content);
            }

            $tabRow[$this->currentTab]->appendAttrib('class', $this->tabActiveClass);

            if ($tabCount > $this->scrollFromSize) {
                $scrollContainer->a('#', $this->nextLabel, array('class' => 'next'));
            }



            return $scrollContainer;
        } else {
            return null;
        }
    }
}