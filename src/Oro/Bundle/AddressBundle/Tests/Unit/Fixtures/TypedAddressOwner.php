<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class TypedAddressOwner
{
    /** @var Collection */
    protected $addresses;

    /**
     * @param TypedAddress[] $addresses
     */
    public function __construct(array $addresses)
    {
        $this->addresses = new ArrayCollection();
        foreach ($addresses as $address) {
            $address->setOwner($this);
            $this->addresses->add($address);
        }
    }

    /**
     * @return TypedAddress[]|Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
}
