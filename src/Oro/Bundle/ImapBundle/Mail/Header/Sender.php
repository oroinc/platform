<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Sender}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\Sender as BaseSender;

/**
 * Sender header that uses overridden GenericHeader during value parsing.
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

        $senderName = '';
        $address = '';
        if ($hasMatches === 1) {
            $senderName = trim($matches['name']);
            $address = $matches['email'];
        }

        if (empty($senderName)) {
            $senderName = null;
        }

        $header->setAddress($address, $senderName);

        return $header;
    }
}
