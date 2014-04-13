<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

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
     */
    public function load(ObjectManager $manager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $adminUser = $this->userManager->createUser();

        $adminUser
            ->setUsername(self::DEFAULT_ADMIN_USERNAME)
            ->setEmail(self::DEFAULT_ADMIN_EMAIL)
            ->setEnabled(true)
            ->setOwner($businessUnit)
            ->setPlainPassword(md5(uniqid(mt_rand(), true)))
            ->addRole($role)
            ->addBusinessUnit($businessUnit);

        $this->userManager->updateUser($adminUser);
    }
}
