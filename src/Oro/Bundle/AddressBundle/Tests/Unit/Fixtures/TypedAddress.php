<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;

class TypedAddress extends AbstractTypedAddress
{
    /**
     * @var TypedAddressOwner
     */
    protected $owner;

    /**
     * @return TypedAddressOwner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param TypedAddressOwner $owner
     */
    public function setOwner(TypedAddressOwner $owner)
    {
        $this->owner = $owner;
    }
}
