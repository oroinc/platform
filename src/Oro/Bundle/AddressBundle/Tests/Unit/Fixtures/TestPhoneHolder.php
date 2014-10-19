<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Model\PhoneHolderInterface;

class TestPhoneHolder implements PhoneHolderInterface
{
    protected $phone;

    public function __construct($phone = null)
    {
        $this->phone = $phone;
    }

    public function getPhoneNumber()
    {
        return $this->phone;
    }

    public function getPhoneNumbers()
    {
        if (empty($this->phone)) {
            return [];
        }

        return [$this->phone];
    }
}
