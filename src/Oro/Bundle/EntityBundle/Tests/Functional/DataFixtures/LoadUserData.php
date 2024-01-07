<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
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

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setOrganization($organization)
            ->setOrganizations(new ArrayCollection([$organization]))
            ->setOwner($organization->getBusinessUnits()->first())
            ->addUserRole($manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]))
            ->setEnabled(true);
        $userManager->updateUser($user);

        $this->setReference($user->getUserIdentifier(), $user);
    }
}
