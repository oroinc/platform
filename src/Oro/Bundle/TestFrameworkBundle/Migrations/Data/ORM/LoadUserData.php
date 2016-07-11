<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData',
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadGroupData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        /** @var User $admin*/
        $admin = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);
        /** @var Group $group */
        $group = $manager->getRepository('OroUserBundle:Group')->findOneBy(['name' => 'Administrators']);
        if (!$admin) {
            /** @var Role $role */
            $role  = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
            $admin = $userManager->createUser();
            $admin
                ->setUsername('admin')
                ->addRole($role);
        }

        $admin
            ->setPlainPassword('admin')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('admin@example.com')
            ->setSalt('');

        if (0 === count($admin->getApiKeys())) {
            /** @var OrganizationRepository $organizationRepo */
            $organizationRepo = $manager->getRepository('OroOrganizationBundle:Organization');
            $organization = $organizationRepo->getFirst();
            $api          = new UserApi();
            $api->setApiKey('admin_api_key')
                ->setUser($admin)
                ->setOrganization($organization);

            $admin->addApiKey($api);
        }

        if (!$admin->hasGroup($group)) {
            $admin->addGroup($group);
        }

        $userManager->updateUser($admin);
    }
}
