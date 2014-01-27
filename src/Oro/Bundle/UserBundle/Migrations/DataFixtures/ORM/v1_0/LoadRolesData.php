<?php

namespace Oro\Bundle\UserBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\Role;

class LoadRolesData extends AbstractFixture
{

    /**
     * Load roles
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $roleAnonymous = new Role('IS_AUTHENTICATED_ANONYMOUSLY');
        $roleAnonymous->setLabel('Anonymous');
        $this->addReference('anon_role', $roleAnonymous);

        $roleUser = new Role('ROLE_USER');
        $roleUser->setLabel('User');
        $this->addReference('user_role', $roleUser);

        $roleSAdmin = new Role('ROLE_ADMINISTRATOR');
        $roleSAdmin->setLabel('Administrator');
        $this->addReference('admin_role', $roleSAdmin);

        $roleManager = new Role('ROLE_MANAGER');
        $roleManager->setLabel('Manager');
        $this->addReference('manager_role', $roleManager);
        if ($this->hasReference('default_business_unit')) {
            $defaultBusinessUnit = $manager
                ->getRepository('OroOrganizationBundle:BusinessUnit')
                ->findOneBy(['name' => 'Main']);
            if ($defaultBusinessUnit) {
                $roleAnonymous->setOwner($defaultBusinessUnit);
                $roleUser->setOwner($defaultBusinessUnit);
                $roleSAdmin->setOwner($defaultBusinessUnit);
                $roleManager->setOwner($defaultBusinessUnit);
            }
        }
        $manager->persist($roleAnonymous);
        $manager->persist($roleUser);
        $manager->persist($roleSAdmin);
        $manager->persist($roleManager);

        $manager->flush();
    }
}
