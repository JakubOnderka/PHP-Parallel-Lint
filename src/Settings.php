<?php
namespace ParallelLint;

/*
Copyright (c) 2012, Jakub Onderka
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those
of the authors and should not be interpreted as representing official policies,
either expressed or implied, of the FreeBSD Project.
 */

class Settings
{
    /**
     * Path to PHP executable
     * @var string
     */
    public $phpExecutable = 'php';

    /**
     * Check code inside PHP opening short tag <? or <?= in PHP 5.3
     * @var bool
     */
    public $shortTag = false;

    /**
     * Check PHP code inside ASP-style <% %> tags.
     * @var bool
     */
    public $aspTags = false;

    /**
     * Path to log file. If null, no log file is created
     * @var string
     */
    public $logFile;

    /**
     * Number of jobs running in same time
     * @var int
     */
    public $parallelJobs = 10;

    /**
     * If path contains directory, only file with these extensions are checked
     * @var array
     */
    public $extensions = array('php', 'phtml', 'php3');

    /**
     * Array of file or directories to check
     * @var array
     */
    public $paths = array();
}