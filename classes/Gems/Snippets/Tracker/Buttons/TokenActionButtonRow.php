<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TokenActionButtonRow.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker\Buttons;

use Gems\Snippets\Generic\CurrentButtonRowSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 8-mei-2015 14:06:48
 */
class TokenActionButtonRow extends CurrentButtonRowSnippet
{
    /**
     * Set the menu items (allows for overruling in subclasses)
     *
     * @param \Gems_Menu_MenuList $menuList
     */
    protected function addButtons(\Gems_Menu_MenuList $menuList)
    {
        $menuList->addByController('respondent', 'show', $this->_('Show respondent'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'show', $this->_('Show token'))
                ->addCurrentSiblings()
                ->addCurrentChildren();
    }
}

