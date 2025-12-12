<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Base class for loading users.
 */
abstract class AbstractLoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        /** @var User $admin */
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        /** @var Group $group */
        $group = $manager->getRepository(Group::class)->findOneBy(['name' => 'Administrators']);

        $businessUnit = $admin->getOrganization()?->getBusinessUnits()?->first();

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

        if ($businessUnit) {
            $admin->addBusinessUnit($businessUnit);
        }

        if (!$admin->hasGroup($group)) {
            $admin->addGroup($group);
        }

        $userManager->updateUser($admin);
    }
}
