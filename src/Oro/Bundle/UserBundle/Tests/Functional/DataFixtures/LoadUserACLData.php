<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserACLData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;
    use SetRolePermissionsTrait;

    public const SIMPLE_USER_ROLE_SYSTEM = 'simple_system_user@example.com';
    public const SIMPLE_USER_ROLE_LOCAL = 'simple_local_user@example.com';
    public const SIMPLE_USER_2_ROLE_LOCAL = 'simple_local_user2@example.com';
    public const SIMPLE_USER_2_ROLE_LOCAL_BU2 = 'simple_local_user2_bu2@example.com';
    public const SIMPLE_USER_ROLE_DEEP_WITHOUT_BU = 'simple_deep_user_without_bu@example.com';

    public const ROLE_SYSTEM = 'ROLE_SYSTEM';
    public const ROLE_LOCAL = 'ROLE_LOCAL';
    public const ROLE_DEEP = 'ROLE_DEEP';

    private static array $users = [
        [
            'email' => self::SIMPLE_USER_ROLE_SYSTEM,
            'role' => self::ROLE_SYSTEM,
            'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
        ],
        [
            'email' => self::SIMPLE_USER_ROLE_LOCAL,
            'role' => self::ROLE_LOCAL,
            'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
        ],
        [
            'email' => self::SIMPLE_USER_2_ROLE_LOCAL,
            'role' => self::ROLE_LOCAL,
            'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
        ],
        [
            'email' => self::SIMPLE_USER_2_ROLE_LOCAL_BU2,
            'role' => self::ROLE_LOCAL,
            'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_2
        ],
        [
            'email' => self::SIMPLE_USER_ROLE_DEEP_WITHOUT_BU,
            'role' => self::ROLE_DEEP,
            'businessUnit' => null
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadBusinessUnitData::class, LoadBusinessUnit::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadRoles($manager);
        $this->loadUsers();
    }

    private function loadRoles(ObjectManager $manager): void
    {
        /* @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $roles = [
            self::ROLE_LOCAL => 'VIEW_LOCAL',
            self::ROLE_SYSTEM => 'VIEW_SYSTEM',
            self::ROLE_DEEP => 'VIEW_DEEP'
        ];
        foreach ($roles as $roleName => $permission) {
            $role = new Role($roleName);
            $role->setLabel($roleName);
            $manager->persist($role);
            $this->setPermissions($aclManager, $role, [
                ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, User::class) => [$permission]
            ]);
            $this->setReference($roleName, $role);
        }
        $manager->flush();
        $aclManager->flush();
    }

    private function loadUsers(): void
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        foreach (self::$users as $item) {
            /** @var User $user */
            $user = $userManager->createUser();
            $user->setUsername($item['email'])
                ->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT))
                ->setPlainPassword($item['email'])
                ->setEmail($item['email'])
                ->setFirstName($item['email'])
                ->addUserRole($this->getReference($item['role']))
                ->setLastName($item['email'])
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setEnabled(true);
            if ($item['businessUnit']) {
                $user->addBusinessUnit($this->getReference($item['businessUnit']));
            }
            $userManager->updateUser($user);
            $this->setReference($item['email'], $user);
        }
    }
}
