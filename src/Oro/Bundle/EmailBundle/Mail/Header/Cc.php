<?php

namespace Oro\Bundle\EmailBundle\Mail\Header;

use Zend\Mail\Header\Cc as BaseHeader;

class Cc extends BaseHeader
{
    public function __construct()
    {
        $this->addressList = new OptionalAddressList();
    }
}
