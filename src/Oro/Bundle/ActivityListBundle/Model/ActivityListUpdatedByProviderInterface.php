<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

interface ActivityListUpdatedByProviderInterface
{
    /**
     * @param object $entity
     *
     * @return User|null
     */
    public function getUpdatedBy($entity);
}
