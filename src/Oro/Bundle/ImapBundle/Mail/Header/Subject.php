<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Subject}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\Subject as BaseSubject;

/**
 * Subject header that uses overridden GenericHeader during value parsing.
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

    /**
     * {@inheritdoc}
     */
    public function setSubject($subject)
    {
        $subject = (string) $subject;

        $this->subject  = $subject;
        $this->encoding = null;

        return $this;
    }
}
