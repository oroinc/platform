<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

class TestPhoneHolder
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

        return [[$this->phone, $this]];
    }
}
