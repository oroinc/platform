<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\GenericHeader}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\GenericHeader as BaseGenericHeader;

/**
 * Common header that uses overridden HeaderWrap during parsing
 * and allows to use extra chars at the header name and value.
 */
class GenericHeader extends BaseGenericHeader
{
    /**
     * {@inheritdoc}
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = self::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);
        $header = new static($name, $value);

        return $header;
    }

    /**
     * {@inheritdoc}
     *
     * Simplify validation - avoid validation exception of header name and value
     */
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);
        if (count($parts) !== 2) {
            throw new Exception\InvalidArgumentException('Header must match with the format "name:value"');
        }

        $parts[1] = ltrim($parts[1]);

        return $parts;
    }
}
