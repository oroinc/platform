<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Subject}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\HeaderWrap;
use \Zend\Mail\Header\Subject as BaseSubject;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class Subject extends BaseSubject
{
    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see Zend\Mail\Header\Subject::fromString}.
     * It is needed to override static call of `GenericHeader::splitHeaderLine`.
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'subject') {
            throw new Exception\InvalidArgumentException('Invalid header line for Subject string');
        }

        $header = new static();
        $header->setSubject($value);

        return $header;
    }
}
