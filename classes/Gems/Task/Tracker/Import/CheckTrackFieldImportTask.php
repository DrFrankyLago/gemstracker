<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTrackFieldImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 18, 2016 7:34:00 PM
 */
class CheckTrackFieldImportTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param array $trackData Nested array of trackdata
     */
    public function execute($lineNr = null, $fieldData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (isset($fieldData['gtf_id_order']) && $fieldData['gtf_id_order']) {
            $import['fieldOrder'][$fieldData['gtf_id_order']] = false;
        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gtf_id_order specified for field at line %d.'),
                    $lineNr
                    ));
        }
        if (isset($fieldData['gtf_field_type']) && $fieldData['gtf_field_type']) {
            $model = $this->loader->getTracker()->createTrackClass('Model\\FieldMaintenanceModel');

            if ($model instanceof \Pulse\Tracker\Model\FieldMaintenanceModel) {
                $fields = $model->getFieldTypes();

                if (! isset($fields[$fieldData['gtf_field_type']])) {
                        $batch->addToCounter('import_errors');
                        $batch->addMessage(sprintf(
                                $this->_('Unknown field type "%s" specified on line %d.'),
                                $fieldData['gtf_field_type'],
                                $lineNr
                                ));
                }
            }

        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No field type specified on line %d.'),
                    $lineNr
                    ));
        }
    }
}
