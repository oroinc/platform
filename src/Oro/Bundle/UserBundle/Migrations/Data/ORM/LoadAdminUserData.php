<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads default admin user.
 */
class LoadAdminUserData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const DEFAULT_ADMIN_USERNAME = 'admin';
    public const DEFAULT_ADMIN_EMAIL = 'admin@example.com';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadRolesData::class,
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $adminRole = $manager->getRepository(Role::class)
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        if (!$adminRole) {
            throw new \RuntimeException('Administrator role should exist.');
        }

        if ($this->isUserWithRoleExist($manager, $adminRole)) {
            return;
        }

        $businessUnit = $manager->getRepository(BusinessUnit::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $organization = $this->getReference('default_organization');

        $userManager = $this->container->get('oro_user.manager');
        $adminUser = $userManager->createUser();
        $adminUser
            ->setUsername(self::DEFAULT_ADMIN_USERNAME)
            ->setEmail(self::DEFAULT_ADMIN_EMAIL)
            ->setEnabled(true)
            ->setOwner($businessUnit)
            ->setPlainPassword(md5(uniqid(mt_rand(), true)))
            ->addUserRole($adminRole)
            ->addBusinessUnit($businessUnit)
            ->setOrganization($organization)
            ->addOrganization($organization);
        $userManager->updatePassword($adminUser);
        $userManager->updateUser($adminUser);
    }

    private function isUserWithRoleExist(ObjectManager $manager, Role $role): bool
    {
        return null !== $manager->getRepository(Role::class)->getFirstMatchedUser($role);
    }
}
