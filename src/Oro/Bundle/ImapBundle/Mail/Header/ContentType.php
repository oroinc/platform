<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\ContentType}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\ContentType as BaseContentType;
use \Zend\Mail\Header\Exception\InvalidArgumentException;
use Oro\Bundle\ImapBundle\Mail\Headers;

/**
 * Content type header that allows to sync emails with non-standard value of the header.
 */
class ContentType extends BaseContentType
{
    /** @var Headers */
    protected static $headers;

    /**
     * {@inheritdoc}
     */
    public static function fromString($headerLine)
    {
        $headerLine = iconv_mime_decode($headerLine, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        list($name, $value) = explode(': ', $headerLine, 2);

        // check to ensure proper header type for this factory
        if (self::getHeaders()->normalizeFieldName($name) !== 'contenttype') {
            throw new InvalidArgumentException('Invalid header line for Content-Type string');
        }

        $value = str_replace(Headers::FOLDING, " ", $value);
        $values = preg_split('#\s*;\s*#', $value);
        $type = array_shift($values);

        $header = new static();
        $header->setType($type);

        if (count($values)) {
            foreach ($values as $keyValuePair) {
                if ($keyValuePair && strpos($keyValuePair, '=') !== false) {
                    list($key, $value) = explode('=', $keyValuePair, 2);
                    $value = trim($value, "'\" \t\n\r\0\x0B");
                    $header->addParameter($key, $value);
                }
            }
        }

        return $header;
    }

    /**
     * @return Headers
     */
    protected static function getHeaders()
    {
        if (self::$headers === null) {
            self::$headers = new Headers();
        }

        return self::$headers;
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $matches = [];
        if (preg_match('/^[a-z-]+\/[a-z0-9.+-]+/i', $type, $matches)) {
            $type = $matches[0];
        }
        $this->type = $type;
        return $this;
    }
}
