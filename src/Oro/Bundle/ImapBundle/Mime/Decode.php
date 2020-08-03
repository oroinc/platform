<?php

/**
 * This file is a copy of {@see Laminas\Mime\Decode}
 *
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
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
