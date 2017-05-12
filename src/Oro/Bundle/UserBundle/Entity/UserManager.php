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

    /**
     * Generates a random string that can be used as a password for a user.
     *
     * @param int $maxLength
     *
     * @return string
     */
    public function generatePassword($maxLength = 30)
    {
        return str_shuffle(
            substr(
                sprintf(
                    '%s%s%s',
                    // get one random upper case letter
                    substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1),
                    // get one random digit
                    substr(str_shuffle('1234567890'), 0, 1),
                    // get some random string
                    strtr(base64_encode(hash('sha256', uniqid((string)mt_rand(), true), true)), '+/=', '___')
                ),
                0,
                $maxLength
            )
        );
    }
}
