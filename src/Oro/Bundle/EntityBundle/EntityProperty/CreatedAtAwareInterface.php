<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Defines the contract for entities that track their creation timestamp.
 *
 * Entities implementing this interface maintain a createdAt property that records
 * when the entity was first created. This is typically managed automatically by
 * the ORM or event listeners.
 */
interface CreatedAtAwareInterface
{
    /**
     * @return \DateTime
     */
    public function getCreatedAt();

    /**
     * @param \DateTime|null $createdAt
     * @return mixed
     */
    public function setCreatedAt(?\DateTime $createdAt = null);
}
