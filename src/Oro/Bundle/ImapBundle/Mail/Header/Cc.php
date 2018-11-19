<?php

namespace Oro\Bundle\ImapBundle\Mail\Header;

/**
 * This file is a copy of {@see Zend\Mail\Header\Cc}
 *
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 */

use \Zend\Mail\Header\Cc as BaseHeader;

/**
 * Cc header that uses overridden OptionalAddressList as the storage of address list.
 */
class Cc extends BaseHeader
{
    /**
     * It is needed to override `new OptionalAddressList()`
     */
    public function __construct()
    {
        $this->addressList = new OptionalAddressList();
    }
}
