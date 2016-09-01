<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Represents an object which has a collection of AbstractPhone
 *
 * @see AbstractPhone
 */
interface PhoneCollectionInterface
{
    /**
     * Get phones
     *
     * @return Collection|AbstractPhone[]
     */
    public function getPhones();
}
