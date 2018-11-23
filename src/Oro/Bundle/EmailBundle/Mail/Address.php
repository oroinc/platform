<?php

/**
 * This file is a copy of {@see Zend\Mail\Address}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail;

use \Zend\Mail\Address as BaseAddress;
use \Zend\Mail\Header\Exception;

class Address extends BaseAddress
{
    /**
     * {@inheritdoc}
     */
    public function __construct($email, $name = null, $comment = null)
    {
        if (!is_string($email)) {
            throw new Exception\InvalidArgumentException('Email must be a string');
        }
        if (null !== $name && !is_string($name)) {
            throw new Exception\InvalidArgumentException('Name must be a string');
        }

        $this->email = $email;
        $this->name = $name;
    }
}
