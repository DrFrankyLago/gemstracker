<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ContentTitleSnippet.php 2534 2015-05-05 18:07:37Z matijsdejong $
 */

namespace Gems\Snippets\Generic;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ContentTitleSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * The title to display
     *
     * @var string
     */
    protected $contentTitle;

    /**
     * Tagname of the HtmlElement to create
     *
     * @var string
     */
    protected $tagName = 'h3';

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
        if ($this->contentTitle) {
            return \MUtil_Html::create($this->tagName, $this->contentTitle, array('class' => 'title'));
        }
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
        return $this->contentTitle && $this->tagName;
    }
}
