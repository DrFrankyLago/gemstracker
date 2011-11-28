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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Generic controller class for showing and editing organizations
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_OrganizationAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Organization_OrganizationTableSnippet';

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Organization_OrganizationEditSnippet';

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Switch the active organization
     */
    public function changeUiAction()
    {
        $user     = $this->loader->getCurrentUser();
        $request  = $this->getRequest();
        $orgId    = urldecode($request->getParam('org'));
        $url      = base64_decode($request->getParam('current_uri'));

        $allowedOrganizations = $user->getAllowedOrganizations();
        if (isset($allowedOrganizations[$orgId])) {
            $user->setCurrentOrganization($orgId);

            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $user->gotoStartPage($this->menu, $request);
            }
            return;
        }

        throw new Exception($this->_('Invalid organization.'));
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        $this->createEditParameters['formTitle'] = $this->_('New organization...');

        parent::createAction();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__organizations');

        $model->setDeleteValues('gor_active', 0, 'gor_add_respondents', 0);

        $model->set('gor_name', 'label', $this->_('Name'), 'size', 25);
        $model->set('gor_location', 'label', $this->_('Location'), 'size', 25);
        $model->set('gor_url', 'label', $this->_('Url'), 'size', 50);
        $model->set('gor_task', 'label', $this->_('Task'), 'size', 25);
        $model->set('gor_contact_name', 'label', $this->_('Contact name'), 'size', 25);
        $model->set('gor_contact_email', 'label', $this->_('Contact email'), 'size', 50, 'validator', 'SimpleEmail');
        if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $model->setIfExists(
                'gor_style', 'label', $this->_('Style'),
                'multiOptions', MUtil_Lazy::call(array($this->escort, 'getStyles'))
            );
        }
        $model->set(
            'gor_iso_lang', 'label', $this->_('Language'),
            'multiOptions', $this->util->getLocalized()->getLanguages(), 'default', 'nl'
        );
        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('gor_active', 'label', $this->_('Active'), 'description', $this->_('Can the organization be used?'), 'elementClass', 'Checkbox', 'multiOptions', $yesNo);
        $model->set('gor_has_login', 'label', $this->_('Login'), 'description', $this->_('Can people login for this organization?'), 'elementClass', 'CheckBox', 'multiOptions', $yesNo);
        $model->set('gor_add_respondents', 'label', $this->_('Accepting'), 'description', $this->_('Can new respondents be added to the organization?'), 'elementClass', 'CheckBox', 'multiOptions', $yesNo);
        $model->set('gor_has_respondents', 'label', $this->_('Respondents'), 'description', $this->_('Does the organization have respondents?'), 'elementClass', 'Exhibitor', 'multiOptions', $yesNo);
        $model->set('gor_respondent_group', 'label', $this->_('Respondent group'), 'description', $this->_('Allows respondents to login.'), 'multiOptions', $this->util->getDbLookup()->getAllowedRespondentGroups());

        if ($detailed) {
            $model->set('gor_name',      'validator', $model->createUniqueValidator('gor_name'));
            $model->set('gor_welcome',   'label', $this->_('Greeting'),  'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
            $model->set('gor_signature', 'label', $this->_('Signature'), 'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        }
        $model->set('gor_accessible_by', 'label', $this->_('Accessible by'), 'description', $this->_('Checked organizations see this organizations respondents.'),
                'elementClass', 'MultiCheckbox', 'multiOptions', $this->util->getDbLookup()->getOrganizations());
        $tp = new MUtil_Model_Type_ConcatenatedRow(':', ', ');
        $tp->apply($model, 'gor_accessible_by');

        if ($detailed && $this->project->multiLocale) {
            $model->set('gor_name', 'description', 'ENGLISH please! Use translation file to translate.');
            $model->set('gor_url',  'description', 'ENGLISH link preferred. Use translation file to translate.');
            $model->set('gor_task', 'description', 'ENGLISH please! Use translation file to translate.');
        }
        $model->setIfExists('gor_code', 'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));

        $model->addColumn("CASE WHEN gor_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        Gems_Model::setChangeFieldsByPrefix($model, 'gor');

        return $model;
    }

    /**
     * Action for showing a delete item page
     */
    public function deleteAction()
    {
        $this->html->h3($this->_('Delete organization'));

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page
     */
    public function editAction()
    {
        $this->createEditParameters['formTitle'] = $this->_("Edit organization");

        parent::editAction();
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $this->html->h3($this->_('Participating organizations'));

        parent::indexAction();
    }

    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        $this->html->h3($this->_('Show organization'));

        parent::showAction();
    }
}
