<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\GenericHeader}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\GenericHeader as BaseGenericHeader;

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
