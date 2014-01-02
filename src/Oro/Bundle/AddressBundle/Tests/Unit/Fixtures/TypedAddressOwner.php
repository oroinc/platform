<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

class TypedAddressOwner
{
    /**
     * @var TypedAddress[]
     */
    protected $addresses;

    /**
     * @param TypedAddress[] $addresses
     */
    public function __construct(array $addresses)
    {
        foreach ($addresses as $address) {
            $address->setOwner($this);
            $this->addresses[] = $address;
        }
    }

    /**
     * @return TypedAddress[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
}
