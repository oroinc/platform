<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class TypedAddressOwner
{
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    /**
     * @return TypedAddress[]|Collection
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
}
