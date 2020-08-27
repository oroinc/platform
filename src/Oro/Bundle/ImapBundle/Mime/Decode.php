<?php

/**
 * Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 * disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other materials provided with the distribution.
 * - Neither the name of Laminas Foundation nor the names of its contributors may be used to endorse or promote
 * products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This file is a copy of {@see Laminas\Mime\Decode}
 */
namespace Oro\Bundle\ImapBundle\Mime;

use Laminas\Mime\Decode as BaseDecode;
use Laminas\Mime\Mime;
use Laminas\Stdlib\ErrorHandler;
use Oro\Bundle\ImapBundle\Mail\Headers;

/**
 * Helper class that converts raw email data.
 */
class Decode extends BaseDecode
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function splitMessage($message, &$headers, &$body, $EOL = Mime::LINEEND, $strict = false)
    {
        if ($message instanceof Headers) {
            $message = $message->toString();
        }
        // check for valid header at first line
        $firstline = strtok($message, "\n");
        if (!preg_match('%^[^\s]+[^:]*:%', $firstline)) {
            $headers = [];
            // we're ignoring \r for now - is this function fast enough and is it safe to assume noone needs \r?
            $body = str_replace(["\r", "\n"], ['', $EOL], $message);
            return;
        }

        // see @ZF2-372, pops the first line off a message if it doesn't contain a header
        if (!$strict) {
            $parts = explode(': ', $firstline, 2);
            if (count($parts) != 2) {
                $message = substr($message, strpos($message, $EOL)+1);
            }
        }

        // find an empty line between headers and body
        // default is set new line
        if (strpos($message, $EOL . $EOL)) {
            [$headers, $body] = explode($EOL.$EOL, $message, 2);
        // next is the standard new line
        } elseif ($EOL != "\r\n" && strpos($message, "\r\n\r\n")) {
            [$headers, $body] = explode("\r\n\r\n", $message, 2);
        // next is the other "standard" new line
        } elseif ($EOL != "\n" && strpos($message, "\n\n")) {
            [$headers, $body] = explode("\n\n", $message, 2);
        // at last resort find anything that looks like a new line
        } else {
            ErrorHandler::start(E_NOTICE | E_WARNING);
            [$headers, $body] = preg_split("%([\r\n]+)\\1%U", $message, 2);
            ErrorHandler::stop();
        }

        $headers = Headers::fromString($headers, $EOL);
    }
}
