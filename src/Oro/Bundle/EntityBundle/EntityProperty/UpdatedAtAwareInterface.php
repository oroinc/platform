<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Defines the contract for entities that track their last update timestamp.
 *
 * Entities implementing this interface maintain an updatedAt property that records
 * when the entity was last modified. This is typically managed automatically by
 * the ORM or event listeners.
 */
interface UpdatedAtAwareInterface
{
    /**
     * @return \DateTime
     */
    public function getUpdatedAt();

    /**
     * @param \DateTime|null $updatedAt
     * @return mixed
     */
    public function setUpdatedAt(?\DateTime $updatedAt = null);

    /**
     * @return bool
     */
    public function isUpdatedAtSet();
}
