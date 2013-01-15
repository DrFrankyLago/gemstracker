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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: JoinTransformer.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Transform that can be used to join models to another model in non-relational
 * ways.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Model_Transform_JoinTransformer implements MUtil_Model_ModelTransformerInterface
{
    /**
     *
     * @var array of join functions
     */
    protected $_joins = array();

    /**
     *
     * @var array of MUtil_Model_ModelAbstract
     */
    protected $_subModels = array();

    public function addModel(MUtil_Model_ModelAbstract $subModel, array $joinFields)
    {
        $name = $subModel->getName();
        $this->_subModels[$name] = $subModel;
        $this->_joins[$name]     = $joinFields;

        if (count($joinFields) > 1) {
            throw new MUtil_Model_ModelException(__CLASS__ . " currently accepts single field joins only.");
        }

        return $this;
    }

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(MUtil_Model_ModelAbstract $model)
    {
        $data = array();
        foreach ($this->_subModels as $sub) {
            foreach ($sub->getItemNames() as $name) {
                if (! $model->has($name)) {
                    $data[$name] = $sub->get($name);
                }
            }
        }
        return $data;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MUtil_Model_ModelAbstract $model, array $data)
    {
        if (! $data) {
            return $data;
        }

        foreach ($this->_subModels as $name => $sub) {
            /* @var $sub MUtil_Model_ModelAbstract */

            if (1 === count($this->_joins[$name])) {
                $mkey = key($this->_joins[$name]);
                $skey = reset($this->_joins[$name]);

                $mfor = MUtil_Ra::column($mkey, $data);

                // MUtil_Echo::track($mfor);

                $sdata = $sub->load(array($skey => $mfor));
                // MUtil_Echo::track($sdata);

                $skeys = array_flip(MUtil_Ra::column($skey, $sdata));
                $empty = array_fill_keys(array_keys(reset($sdata)), null);

                foreach ($data as &$mrow) {
                    $mfind = $mrow[$mkey];

                    if (isset($skeys[$mfind])) {
                        $mrow += $sdata[$skeys[$mfind]];
                    } else {
                        $mrow += $empty;
                    }
                }
            }
        }
        // MUtil_Echo::track($data);

        return $data;
    }
}
