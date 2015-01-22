<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\UserBundle\Entity\User;

class PlaceholderFilter
{
    /**
     * Checks the object is an instance of a given class.
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
    {
        if ($entity instanceof User) {
            return true;
        }
        return false;
    }
}
