<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

class LoadAdminUserData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const DEFAULT_ADMIN_USERNAME = 'admin';
    const DEFAULT_ADMIN_EMAIL = 'admin@example.com';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData',
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->userManager = $container->get('oro_user.manager');
    }

    /**
     * Load default administrator
     *
     * @param ObjectManager $manager
     * @throws \RuntimeException
     */
    public function load(ObjectManager $manager)
    {
        $adminRole = $manager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        if (!$adminRole) {
            throw new \RuntimeException('Administrator role should exist.');
        }

        if ($this->isUserWithRoleExist($manager, $adminRole)) {
            return;
        }

        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $organization = $this->getReference('default_organization');

        $adminUser = $this->userManager->createUser();

        $adminUser
            ->setUsername(self::DEFAULT_ADMIN_USERNAME)
            ->setEmail(self::DEFAULT_ADMIN_EMAIL)
            ->setEnabled(true)
            ->setOwner($businessUnit)
            ->setPlainPassword(md5(uniqid(mt_rand(), true)). '1Q')
            ->addRole($adminRole)
            ->addBusinessUnit($businessUnit)
            ->setOrganization($organization)
            ->addOrganization($organization);

        $this->userManager->updateUser($adminUser);
    }

    /**
     * @param ObjectManager $manager
     * @param Role $role
     * @return bool
     */
    protected function isUserWithRoleExist(ObjectManager $manager, Role $role)
    {
        return null !== $manager->getRepository('OroUserBundle:Role')->getFirstMatchedUser($role);
    }
}
