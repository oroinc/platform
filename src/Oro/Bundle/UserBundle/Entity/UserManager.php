<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserManager extends BaseUserManager
{
    const AUTH_STATUS_ENUM_CODE = 'auth_status';

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /**
     * @param string $class
     * @param ManagerRegistry $registry
     * @param EncoderFactoryInterface $encoderFactory
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(
        $class,
        ManagerRegistry $registry,
        EncoderFactoryInterface $encoderFactory,
        EnumValueProvider $enumValueProvider
    ) {
        parent::__construct($class, $registry, $encoderFactory);

        $this->enumValueProvider = $enumValueProvider;
    }

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
     * Sets AuthStatus with enum value id
     *
     * @param User $user
     * @param string $authStatus EnumValueId
     */
    public function setAuthStatus(User $user, $authStatus)
    {
        $user->setAuthStatus($this->enumValueProvider->getEnumValueByCode(self::AUTH_STATUS_ENUM_CODE, $authStatus));
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
