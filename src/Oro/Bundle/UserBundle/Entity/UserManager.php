<?php

namespace Oro\Bundle\UserBundle\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserManager extends BaseUserManager
{
    /**
     * Return related repository
     *
     * @param User $user
     * @param Organization $organization
     *
     * @return UserApi
     */
    public function getApi(User $user, Organization $organization)
    {
        return $this->getStorageManager()->getRepository('OroUserBundle:UserApi')->getApi($user, $organization);
    }
}
