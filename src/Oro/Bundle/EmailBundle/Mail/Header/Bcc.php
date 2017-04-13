<?php

namespace Oro\Bundle\EmailBundle\Mail\Header;

use Zend\Mail\Header\Bcc as BaseHeader;

class Bcc extends BaseHeader
{
    public function __construct()
    {
        $this->addressList = new OptionalAddressList();
    }
}
