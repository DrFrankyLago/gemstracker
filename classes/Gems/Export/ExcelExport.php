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
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Export_ExcelExport extends \Gems_Export_ExportAbstract
{
    protected $fileExtension = '.xls';

    /**
     * return name of the specific export
     */
    public function getName() {
        return 'ExcelExport';
    }

    /**
     * form elements for extra options for this particular export option
     */
    public function getFormElements(&$form, &$data)
    {
        $element = $form->createElement('multiCheckbox', 'format');
        $element->setLabel($this->_('Excel options'))
            ->setMultiOptions(array(
                'formatVariable' => $this->_('Export questions instead of variable names'),
                'formatAnswer' => $this->_('Format answers')
            ));
        $elements[] = $element;

        return $elements;
    }

    /**
     * Sets the default form values when this export type is first chosen
     *
     * @return array
     */
    public function getDefaultFormValues()
    {
        return array('format'=>array('formatVariable', 'formatAnswer'));
    }

    /**
     * Add headers to a specific file
     */
    protected function addheader($filename)
    {
        MUtil_Echo::track($this->data);
        $file = fopen($filename, 'w');
        fwrite($file, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=UTF-8">
<meta name=ProgId content=Excel.Sheet>
<meta name=Generator content="Microsoft Excel 11">
<style>
    /* Default styles for tables */

    table {
        border-collapse: collapse;
        border: .5pt solid #000000;
    }

    tr th {
        font-weight: bold;
        padding: 3px 8px;
        border: .5pt solid #000000;
        background: #c0c0c0;
    }
    tr td {
        padding: 3px 8px;
        border: .5pt solid #000000;
    }
    td {
        mso-number-format:"\@";
    }
    td.number {
        mso-number-format:"0\.00";
    }
    td.date {
        mso-number-format:"yyyy\-mm\-dd";
    }
    td.datetime {
        mso-number-format:"dd\-mm\-yyyy hh\:mm\:ss";
    }
    td.time {
        mso-number-format:"hh\:mm\:ss";
    }
</style>
</head>
<body>');

        //Only for the first row: output headers
        $labeledCols = $this->model->getColNames('label');
        $output = "<table>\r\n";
        $output .= "\t<thead>\r\n";
        $output .= "\t\t<tr>\r\n";
        MUtil_Echo::track($this->data[$this->getName()]);
        if (isset($this->data[$this->getName()])) {
            MUtil_Echo::track('value exists');
            if (isset($this->data[$this->getName()]['format'])) {
                MUtil_Echo::track('format exists', $this->data[$this->getName()]['format']);
                if (in_array('formatVariable', $this->data[$this->getName()]['format'])) {
                    MUtil_Echo::track('formatVariable is found in Array');
                }
            }
        }
        if (isset($this->data[$this->getName()]) && isset($this->data[$this->getName()]['format']) && in_array('formatVariable', $this->data[$this->getName()]['format'])) {
            MUtil_Echo::track('formatVariable!');
            foreach ($labeledCols as $columnName) {
                if ($label = $this->model->get($columnName, 'label')) {

                    $output .= "\t\t\t<th>" . $label. "</th>\r\n";
                }
            }
        } else {
            foreach ($labeledCols as $columnName) {
                if ($label = $this->model->get($columnName, 'label')) {

                    $output .= "\t\t\t<th>" . $columnName. "</th>\r\n";
                }
            }
        }
        $output .= "\t\t</tr>\r\n";
        $output .= "\t</thead>\r\n";
        $output .= "\t<tbody>\r\n";

        fwrite($file, $output);
        fclose($file);
    }

    public function addRows($exportModelSourceName, $filter, $data, $tempFilename)
    {
        $name = $this->getName();
        if (!(isset($data[$name]) && isset($data[$name]['format']) && in_array('formatAnswer', $data[$name]['format']))) {
            MUtil_Echo::track('Do not format answers');
            $this->modelFilterAttributes = array('formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay');
        }
        parent::addRows($exportModelSourceName, $filter, $data, $tempFilename);
    }

    public function addRow($row, $file)
    {
        fwrite($file, "\t\t<tr>\r\n");
        $exportRow = $this->filterRow($row);
        $labeledCols = $this->model->getColNames('label');
        foreach($labeledCols as $columnName) {
            $result = $exportRow[$columnName];
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $output = '<td class="date">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $output = '<td class="datetime">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $output = '<td class="time">'.$result.'</td>';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    if (isset($options['multiOptions']) && (is_numeric(array_shift($options['multiOptions'])))) {
                        $output = '<td>'.$result.'</td>';
                    } else {
                        $output = '<td class="number">'.$result.'</td>';
                    }
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    $output = '<td>'.$result.'</td>';
                    break;
            }
            fwrite($file, $output);
        }
        fwrite($file, "\t\t</tr>\r\n");
    }

    public function addFooter($filename)
    {
        $file = fopen($filename, 'a');
        fwrite($file, '            </tbody>
        </table>
    </body>
</html>');
        fclose($file);
    }

    protected function preprocessModel()
    {
        //print_r(get_class($this->model));
        $labeledCols = $this->model->getColNames('label');
        foreach($labeledCols as $columnName) {
            $options = array();
            $type = $this->model->get($columnName, 'type');
            switch ($type) {
                case \MUtil_Model::TYPE_DATE:
                    $options['storageFormat'] = 'yyyy-MM-dd';
                    $options['dateFormat']    = 'yyyy-MM-dd';
                    break;

                case \MUtil_Model::TYPE_DATETIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'dd-MM-yyyy HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_TIME:
                    $options['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                    $options['dateFormat']    = 'HH:mm:ss';
                    break;

                case \MUtil_Model::TYPE_NUMERIC:
                    break;

                //When no type set... assume string
                case \MUtil_Model::TYPE_STRING:
                default:
                    //$type                      = \MUtil_Model::TYPE_STRING;
                    //$options['formatFunction'] = 'formatString';
                    break;
            }
            $options['type']           = $type;
            $this->model->set($columnName, $options);
        }
    }
}