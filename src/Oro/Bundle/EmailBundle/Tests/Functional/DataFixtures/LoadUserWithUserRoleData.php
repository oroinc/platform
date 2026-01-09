<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserWithUserRoleData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadBusinessUnit::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => 'ROLE_USER']);

        $user = $userManager->createUser();
        $user
            ->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT))
            ->setUsername('limited_user')
            ->setEmail('limited_user@test.com')
            ->setPlainPassword('limited_user')
            ->addUserRole($role)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($organization->getBusinessUnits()->first())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEnabled(true)
            ->setSalt('');

        $userManager->updateUser($user);
        $manager->flush();

        $this->setReference($user->getUserIdentifier(), $user);
    }
}
