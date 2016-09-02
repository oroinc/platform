<?php

/**
 * This file is a copy of {@see Zend\Mime\Decode}
 *
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace Oro\Bundle\EmailBundle\Mime;

use \Zend\Mime\Decode as BaseDecode;
use \Zend\Mime\Mime;
use \Zend\Stdlib\ErrorHandler;

use Oro\Bundle\EmailBundle\Mail\Headers;

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
            $headers = array();
            // TODO: we're ignoring \r for now - is this function fast enough and is it safe to assume noone needs \r?
            $body = str_replace(array("\r", "\n"), array('', $EOL), $message);
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
            list($headers, $body) = explode($EOL . $EOL, $message, 2);
            // next is the standard new line
        } elseif ($EOL != "\r\n" && strpos($message, "\r\n\r\n")) {
            list($headers, $body) = explode("\r\n\r\n", $message, 2);
            // next is the other "standard" new line
        } elseif ($EOL != "\n" && strpos($message, "\n\n")) {
            list($headers, $body) = explode("\n\n", $message, 2);
            // at last resort find anything that looks like a new line
        } else {
            ErrorHandler::start(E_NOTICE|E_WARNING);
            list($headers, $body) = preg_split("%([\r\n]+)\\1%U", $message, 2);
            ErrorHandler::stop();
        }

        $headers = Headers::fromString($headers, $EOL);
    }
}
