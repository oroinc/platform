<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\ContentTransferEncoding}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Zend\Mail\Header\ContentTransferEncoding as BaseContentTransferEncoding;
use Zend\Mail\Header\Exception\InvalidArgumentException;

/**
 * Content transfer header that adds support of additional encoding types
 * and allows to process the header value with extra data.
 */
class ContentTransferEncoding extends BaseContentTransferEncoding
{
    /**
     * {@inheritdoc}
     */
    protected static $allowedTransferEncodings = [
        '7bit',
        '8bit',
        'quoted-printable',
        'base64',
        'binary',
        'utf-8',
        'windows-1251'
        /*
         * not implemented:
         * x-token: 'X-'
         */
    ];

    /**
     * {@inheritdoc}
     */
    public function setTransferEncoding($transferEncoding)
    {
        // Per RFC 1521, the value of the header is not case sensitive
        $transferEncoding = strtolower($transferEncoding);

        // support of '7bit boundary="_av-21755293853469785109"' and '7bi tboundary' header values
        $transferEncodingString = str_replace(' ', '', $transferEncoding);
        foreach (static::$allowedTransferEncodings as $encoding) {
            if (strpos($transferEncodingString, $encoding) === 0) {
                $this->transferEncoding = $encoding;

                return $this;
            }
        }

        throw new InvalidArgumentException(sprintf(
            '%s expects one of "'. implode(', ', static::$allowedTransferEncodings) . '"; received "%s"',
            __METHOD__,
            (string) $transferEncoding
        ));
    }
}
