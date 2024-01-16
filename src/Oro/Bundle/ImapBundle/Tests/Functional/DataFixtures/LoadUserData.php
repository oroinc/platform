<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const SIMPLE_USER_ENABLED = 'simple_user_enabled';
    public const SIMPLE_USER_ENABLED_PASSWORD = 'simple_user_enabled';
    public const SIMPLE_USER_DISABLED = 'simple_user_disabled';
    public const SIMPLE_USER_DISABLED_PASSWORD = 'simple_user_disabled';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $user1 = $userManager->createUser();
        $user1->setUsername(self::SIMPLE_USER_ENABLED)
            ->setPlainPassword(self::SIMPLE_USER_ENABLED_PASSWORD)
            ->setEmail('simple_user@example.com')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user1);
        $this->setReference(self::SIMPLE_USER_ENABLED, $user1);

        $user2 = $userManager->createUser();
        $user2->setUsername(self::SIMPLE_USER_DISABLED)
            ->setPlainPassword(self::SIMPLE_USER_DISABLED_PASSWORD)
            ->setFirstName('Merry')
            ->setLastName('Backwards')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(false);
        $userManager->updateUser($user2);
        $this->setReference(self::SIMPLE_USER_DISABLED, $user2);
    }
}
