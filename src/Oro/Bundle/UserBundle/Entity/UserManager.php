<?php

namespace Oro\Bundle\UserBundle\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserManager extends BaseUserManager
{
    /**
     * Return related repository
     *
     * @param User         $user
     * @param Organization $organization
     *
     * @return UserApi
     */
    public function getApi(User $user, Organization $organization)
    {
        return $this->getStorageManager()->getRepository('OroUserBundle:UserApi')->getApi($user, $organization);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(UserInterface $user, $flush = true)
    {
        $this->assertRoles($user);

        parent::updateUser($user, $flush);
    }

    /**
     * We need to make sure to have at least one role.
     *
     * @param UserInterface $user
     * @throws \RuntimeException
     */
    protected function assertRoles(UserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            $role = $this->getStorageManager()
                ->getRepository('OroUserBundle:Role')
                ->findOneBy(['role' => User::ROLE_DEFAULT]);

            if (!$role) {
                throw new \RuntimeException('Default user role not found');
            }

            $user->addRole($role);
        }
    }
}
