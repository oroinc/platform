<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

class LoadRolesData extends AbstractFixture implements DependentFixtureInterface
{
    const ROLE_ANONYMOUS     = 'IS_AUTHENTICATED_ANONYMOUSLY';
    const ROLE_USER          = 'ROLE_USER';
    const ROLE_ADMINISTRATOR = 'ROLE_ADMINISTRATOR';
    const ROLE_MANAGER       = 'ROLE_MANAGER';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'];
    }

    /**
     * Load roles
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $roleAnonymous = new Role(self::ROLE_ANONYMOUS);
        $roleAnonymous->setLabel('Anonymous');

        $roleUser = new Role(self::ROLE_USER);
        $roleUser->setLabel('User');

        $roleSAdmin = new Role(self::ROLE_ADMINISTRATOR);
        $roleSAdmin->setLabel('Administrator');

        $roleManager = new Role(self::ROLE_MANAGER);
        $roleManager->setLabel('Manager');

        $manager->persist($roleAnonymous);
        $manager->persist($roleUser);
        $manager->persist($roleSAdmin);
        $manager->persist($roleManager);

        $manager->flush();
    }
}
