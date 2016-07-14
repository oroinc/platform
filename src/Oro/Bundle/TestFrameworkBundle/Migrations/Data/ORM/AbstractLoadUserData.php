<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity;

abstract class AbstractLoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Entity\UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        /** @var Entity\User $admin */
        $admin = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);
        /** @var Entity\Group $group */
        $group = $manager->getRepository('OroUserBundle:Group')->findOneBy(['name' => 'Administrators']);
        if (!$admin) {
            /** @var Entity\Role $role */
            $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
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
            $api = new Entity\UserApi();
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
