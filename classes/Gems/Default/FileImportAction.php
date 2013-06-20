<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: FileImportAction.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Default_FileImportAction extends Gems_Controller_Action
{
    /**
     * @var Gems_Menu
     */
    public $menu;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * To initiate the use of $this->html from the code call $this->initHtml()
     *
     * Overrules $useRawOutput.
     *
     * @see $useRawOutput
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    public function autoAction()
    {
        $filename = $this->_getParam('file');
        $this->html->div('Auto for: ' . $this->loader->getCurrentUser()->getLoginName() . ' file ' . $filename);

        if (!file_exists($filename)) {
            $file = GEMS_ROOT_DIR . '/' . $filename;

            if (file_exists($file)) {
                $filename = $file;
            }

        }
        $files = MUtil_File::getFilesRecursive($filename);

        foreach ($files as $filename) {
           $model = new MUtil_Model_TabbedTextModel($filename);
           $data = $model->load();
           // MUtil_Echo::track($data);
           $table = MUtil_Html_TableElement::createArray($data, $filename, true);
           $table->class = 'browser';
           $this->html->append($table);
        }

        // $this->html->ul($files);
    }

    public function indexAction()
    {
        // print_r($this->_request->getParams());
        $menuList = $this->menu->getMenuList();

        foreach ($this->menu->getCurrentChildren() as $child) {
            if ($child instanceof Gems_Menu_SubMenuItem) {
                $chAction = $child->get('action');
                $chContr  = $child->get('controller');
                $menuList->addByController($chContr, $chAction);
            }
        }

        $this->html->div($menuList);
    }
}
