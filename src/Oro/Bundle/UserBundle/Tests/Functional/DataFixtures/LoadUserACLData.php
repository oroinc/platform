<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

class LoadUserACLData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const SIMPLE_USER_ROLE_SYSTEM = 'simple_system_user@example.com';
    const SIMPLE_USER_ROLE_LOCAL = 'simple_local_user@example.com';
    const SIMPLE_USER_2_ROLE_LOCAL = 'simple_local_user2@example.com';
    const SIMPLE_USER_2_ROLE_LOCAL_BU2 = 'simple_local_user2_bu2@example.com';
    const SIMPLE_USER_ROLE_DEEP_WITHOUT_BU = 'simple_deep_user_without_bu@example.com';

    const ROLE_SYSTEM = 'ROLE_SYSTEM';
    const ROLE_LOCAL = 'ROLE_LOCAL';
    const ROLE_DEEP = 'ROLE_DEEP';

    /**
     * @return array
     */
    public static function getUsers()
    {
        return [
            [
                'email' => static::SIMPLE_USER_ROLE_SYSTEM,
                'role' => static::ROLE_SYSTEM,
                'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
            ],
            [
                'email' => static::SIMPLE_USER_ROLE_LOCAL,
                'role' => static::ROLE_LOCAL,
                'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
            ],
            [
                'email' => static::SIMPLE_USER_2_ROLE_LOCAL,
                'role' => static::ROLE_LOCAL,
                'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_1
            ],
            [
                'email' => static::SIMPLE_USER_2_ROLE_LOCAL_BU2,
                'role' => static::ROLE_LOCAL,
                'businessUnit' => LoadBusinessUnitData::BUSINESS_UNIT_2
            ],
            [
                'email' => static::SIMPLE_USER_ROLE_DEEP_WITHOUT_BU,
                'role' => static::ROLE_DEEP,
                'businessUnit' => null
            ]
        ];
    }

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadBusinessUnitData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadRoles($manager);
        $this->loadUsers($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadRoles(ObjectManager $manager)
    {
        /* @var $aclManager AclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        $roles = [
            static::ROLE_LOCAL => 'VIEW_LOCAL',
            static::ROLE_SYSTEM => 'VIEW_SYSTEM',
            static::ROLE_DEEP => 'VIEW_DEEP'
        ];
        foreach ($roles as $key => $roleName) {
            $role = new Role($key);
            $role->setLabel($key);
            $manager->persist($role);

            $this->setRolePermissions($aclManager, $role, User::class, $roleName);

            $this->setReference($key, $role);
        }

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadUsers($manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach (static::getUsers() as $item) {
            /** @var RoleInterface $role */
            $role = $this->getReference($item['role']);
            /** @var User $user */
            $user = $userManager->createUser();

            $user->setUsername($item['email'])
                ->setPlainPassword($item['email'])
                ->setEmail($item['email'])
                ->setFirstName($item['email'])
                ->addRole($role)
                ->setLastName($item['email'])
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setEnabled(true);

            if ($item['businessUnit']) {
                /** @var BusinessUnit $businessUnit */
                $businessUnit = $this->getReference($item['businessUnit']);
                $user->addBusinessUnit($businessUnit);
            }
            $userManager->updateUser($user);

            $this->setReference($item['email'], $user);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param Role $role
     * @param string $className
     * @param string $allowedAcls
     */
    protected function setRolePermissions(AclManager $aclManager, Role $role, $className, $allowedAcls)
    {
        if ($aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            $oid = $aclManager->getOid('entity:' . $className);

            $builder = $aclManager->getMaskBuilder($oid);
            $builder->reset()->get();
            $mask = $builder->add($allowedAcls)->get();

            $aclManager->setPermission($sid, $oid, $mask);
        }
    }
}
