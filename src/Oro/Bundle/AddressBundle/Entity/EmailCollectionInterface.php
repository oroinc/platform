<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Represents an object which has a collection of AbstractEmail
 *
 * @see AbstractEmail
 */
interface EmailCollectionInterface
{
    /**
     * Get emails
     *
     * @return Collection|AbstractEmail[]
     */
    public function getEmails();
}
