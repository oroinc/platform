<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Bcc}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Bcc as BaseHeader;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
 */
class Bcc extends BaseHeader
{
    /**
     * It is needed to override `new OptionalAddressList()`
     */
    public function __construct()
    {
        $this->addressList = new OptionalAddressList();
    }
}
