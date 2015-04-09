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
 * @package    MUtil
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CommandLine.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Command line repsonse client for Zend. Thanks to
 * http://stackoverflow.com/questions/2325338/running-a-zend-framework-action-from-command-line
 *
 * @package    MUtil
 * @subpackage Controller
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Controller_Request_Cli extends \Zend_Controller_Request_Abstract
{
    /**
     * @var string
     */
    protected $_userName;

    /**
     * @var int
     */
    protected $_userOrg;

    /**
     * @var string
     */
    protected $_userPassword;

    /**
     * Everything in REQUEST_URI before PATH_INFO not including the filename
     * <img src="<?=$basePath?>/images/zend.png"/>
     *
     * @return string
     */
    public function getBasePath()
    {
        return '';
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a member of the $_GET superglobal
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key) {
            return $this->getParams();
        }

        return $this->getParam($key, $default);
    }

    /**
     * Return the command line name of the user
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->_userName;
    }

    /**
     * Return the command line number of the organization
     *
     * @return int
     */
    public function getUserOrganization()
    {
        return $this->_userOrg;
    }

    /**
     * Return the command line password of the user
     *
     * @return string
     */
    public function getUserPassword()
    {
        return $this->_userPassword;
    }

    /**
     * Has the user login information
     *
     * @return boolean
     */
    public function hasUserLogin()
    {
        return (boolean) $this->_userName || $this->_userOrg || $this->_userPassword;
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost()
    {
        return false;
    }

    /**
     * Store the user login information.
     *
     * @param string $userName
     * @param int $organization
     * @param string $password
     * @return \MUtil_Controller_Request_Cli (continuation pattern)
     */
    public function setUserLogin($userName, $organization, $password)
    {
        $this->_userName     = $userName;
        $this->_userOrg      = $organization;
        $this->_userPassword = $password;
        return $this;
    }
}
