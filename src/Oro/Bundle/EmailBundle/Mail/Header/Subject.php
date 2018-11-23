<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Subject}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\Subject as BaseSubject;

class Subject extends BaseSubject
{
    /**
     * {@inheritdoc}
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
