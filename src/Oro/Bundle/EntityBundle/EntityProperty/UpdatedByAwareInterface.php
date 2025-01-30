<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

use Oro\Bundle\UserBundle\Entity\User;

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
