<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\Bcc}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use \Zend\Mail\Header\Bcc as BaseHeader;

/**
 * Bcc header that uses overridden OptionalAddressList as the storage of address list.
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
