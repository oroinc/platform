<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Defines the contract for entities that track who last updated them.
 *
 * Entities implementing this interface maintain an `updatedBy` property that records
 * which user last modified the entity. This is typically managed automatically by
 * event listeners that capture the current user context.
 */
interface UpdatedByAwareInterface
{
    /**
     * @return User
     */
    public function getUpdatedBy();

    /**
     * @param User|null $updatedBy
     * @return mixed
     */
    public function setUpdatedBy(?User $updatedBy = null);

    /**
     * @return bool
     */
    public function isUpdatedBySet();
}
