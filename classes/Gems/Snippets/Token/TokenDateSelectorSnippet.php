<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 * Displays a dateSelector
 *
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class TokenDateSelectorSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Selector_DateSelectorAbstract
     */
    protected $dateSelector;
    
    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The raw search data
     *
     * @var array
     */
    protected $searchData;
    
    /**
     * The $request param that stores the ascending sort
     *
     * @var string
     */
    protected $sortParamAsc;

    /**
     * The $request param that stores the descending sort
     *
     * @var string
     */
    protected $sortParamDesc;

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->sortParamAsc) {
            $this->dateSelector->getModel()->setSortParamAsc($this->sortParamAsc);
        }
        if ($this->sortParamDesc) {
            $this->dateSelector->getModel()->setSortParamDesc($this->sortParamDesc);
        }
        
        $model = $this->dateSelector->getModel();
        $filter = $model->getFilter();
        // Unset sorts from the filter
        unset($filter[$model->getSortParamAsc()]);
        unset($filter[$model->getSortParamDesc()]);

        $model->setFilter($filter);
        
        return $this->dateSelector->getTable($this->searchData);
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {        
        return (boolean) $this->dateSelector;
    }
}
