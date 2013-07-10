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
 * @subpackage File
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: File.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage File
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_File
{
    /**
     * Ensure the directory does really exist or throw an exception othewise
     *
     * @param string $dir The path of the directory
     * @param int $mode Unix file mask mode, ignored on Windows
     * @return string the directory
     * @throws Zend_Exception
     */
    public static function ensureDir($dir, $mode = 0777)
    {
        if (! is_dir($dir)) {
            if (! @mkdir($dir, $mode, true)) {
                throw new Zend_Exception(sprintf("Could not create '%s' directory: %s", $dir, error_get_last()));
            }
        }

        return $dir;
    }

    /**
     * Returns an array containing all the files (not directories) in a
     * recursive directory listing from $dir.
     *
     * @param string $dir
     * @return array
     */
    public static function getFilesRecursive($dir)
    {
        $results = array();

        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    if (('.' !== $file) && ('..' !== $file)) {
                        $results = array_merge($results, self::getFilesRecursive($path));
                    }
                } else {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    /**
     * Creates a temporary filename in the directory but first cleans
     * up any files older than $keepFor in that directory.
     *
     * When no directory is used sys_get_temp_dir() is used and no cleanup is performed.
     *
     * @param string $dir The directory for the files
     * @param string $prefix Optional prefix
     * @param int $keepFor The number of second a file is kept, use 0 for always
     * @return string
     */
    public static function getTemporaryIn($dir = null, $prefix = null, $keepFor = 86400)
    {
        if (null === $dir) {
            return tempnam(sys_get_temp_dir(), $prefix);
        }

        self::ensureDir($dir);

        if ($keepFor) {
            // Clean up old temporaries
            foreach (glob($dir . '/*', GLOB_NOSORT) as $filename) {
                if ((!is_dir($filename)) && (filemtime($filename) + $keepFor < time())) {
                    @unlink($filename);
                }
            }
        }

        return tempnam($dir, $prefix);
    }
}