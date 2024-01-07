<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Base class for loading users.
 */
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

        /** @var User $admin */
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        /** @var Group $group */
        $group = $manager->getRepository(Group::class)->findOneBy(['name' => 'Administrators']);
        if (!$admin) {
            /** @var Role $role */
            $role = $manager->getRepository(Role::class)->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
            $admin = $userManager->createUser();
            $admin
                ->setUsername('admin')
                ->addUserRole($role);
        }

        $admin
            ->setPlainPassword('admin')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('admin@example.com')
            ->setSalt('');

        if (0 === count($admin->getApiKeys())) {
            /** @var OrganizationRepository $organizationRepo */
            $organizationRepo = $manager->getRepository(Organization::class);
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
