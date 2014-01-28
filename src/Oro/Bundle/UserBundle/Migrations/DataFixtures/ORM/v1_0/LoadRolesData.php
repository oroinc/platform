<?php

namespace Oro\Bundle\UserBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0\LoadBusinessUnitData;

class LoadRolesData extends AbstractFixture
{
    const ROLE_ANONYMOUS = 'IS_AUTHENTICATED_ANONYMOUSLY';
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMINISTRATOR = 'ROLE_ADMINISTRATOR';
    const ROLE_MANAGER = 'ROLE_MANAGER';

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

        $defaultBusinessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadBusinessUnitData::MAIN_BUSINESS_UNIT]);

        if ($defaultBusinessUnit) {
            $roleAnonymous->setOwner($defaultBusinessUnit);
            $roleUser->setOwner($defaultBusinessUnit);
            $roleSAdmin->setOwner($defaultBusinessUnit);
            $roleManager->setOwner($defaultBusinessUnit);
        }

        $manager->persist($roleAnonymous);
        $manager->persist($roleUser);
        $manager->persist($roleSAdmin);
        $manager->persist($roleManager);

        $manager->flush();
    }
}
