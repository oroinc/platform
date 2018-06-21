<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Sender}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\HeaderWrap;
use \Zend\Mail\Header\Sender as BaseSender;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class Sender extends BaseSender
{
    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see Zend\Mail\Header\Sender::fromString}.
     * It is needed to override static call of `GenericHeader::splitHeaderLine`.
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'sender') {
            throw new Exception\InvalidArgumentException('Invalid header name for Sender string');
        }

        $header = new static();

        /**
         * matches the header value so that the email must be enclosed by < > when a name is present
         * 'name' and 'email' capture groups correspond respectively to 'display-name' and 'addr-spec' in the ABNF
         * @see https://tools.ietf.org/html/rfc5322#section-3.4
         */
        $hasMatches = preg_match(
            '/^(?:(?P<name>.+)|((name>.+)\s))?(?(name)<|<?)(?P<email>[^\s]+?)(?(name)>|>?)$/',
            $value,
            $matches
        );

        if ($hasMatches !== 1) {
            throw new Exception\InvalidArgumentException('Invalid header value for Sender string');
        }

        $senderName = trim($matches['name']);

        if (empty($senderName)) {
            $senderName = null;
        }

        $header->setAddress($matches['email'], $senderName);

        return $header;
    }
}
