<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\UserBundle\Entity\User;

class PlaceholderFilter
{
    /**
     * Checks if the object is an instance of a given class.
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
    {
        if ($entity instanceof User && $entity->isEnabled()) {
            return true;
        }
        return false;
    }
}
