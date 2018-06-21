<?php

/**
 * This file is a copy of {@see Zend\Mail\Address}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail;

use \Zend\Mail\Address as BaseAddress;
use \Zend\Mail\Header\Exception;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class Address extends BaseAddress
{
    /**
     * {@inheritdoc}
     *
     * Simplify validation - avoid validation exception in case of invalid email address
     */
    public function __construct($email, $name = null, $comment = null)
    {
        if (!is_string($email)) {
            throw new Exception\InvalidArgumentException('Email must be a string');
        }

        if (preg_match("/[\r\n]/", $email)) {
            throw new Exception\InvalidArgumentException('CRLF injection detected');
        }

        if (null !== $name && !is_string($name)) {
            throw new Exception\InvalidArgumentException('Name must be a string');
        }

        if (null !== $name && preg_match("/[\r\n]/", $name)) {
            throw new Exception\InvalidArgumentException('CRLF injection detected');
        }

        $this->email = $email;
        $this->name = $name;
    }
}
