<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Core\Role\RoleInterface;

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

    /**
     * {@inheritdoc}
     */
    protected function assertRoles(UserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            $metadata = $this->getStorageManager()->getClassMetadata(ClassUtils::getClass($user));
            $roleClassName = $metadata->getAssociationTargetClass('roles');

            if (!is_a($roleClassName, 'Symfony\Component\Security\Core\Role\RoleInterface', true)) {
                throw new \RuntimeException(
                    sprintf('Expected Symfony\Component\Security\Core\Role\RoleInterface, %s given', $roleClassName)
                );
            }

            /** @var RoleInterface $role */
            $role = $this->getStorageManager()
                ->getRepository($roleClassName)
                ->findOneBy(['role' => User::ROLE_DEFAULT]);

            if (!$role) {
                throw new \RuntimeException('Default user role not found');
            }

            $user->addRole($role);
        }
    }
}
