<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;

class LoadOrganizationsWithUsersData extends AbstractFixture implements DependentFixtureInterface
{
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
        /** @var Organization $defaultOrg */
        $defaultOrg = $this->getReference(LoadOrganization::ORGANIZATION);

        $org1 = $this->createOrganization($manager, 'segment.organization.1');
        $org2 = $this->createOrganization($manager, 'segment.organization.2');

        $this->createUser($manager, $defaultOrg, $org1, 'fn1', 'ln1');
        $this->createUser($manager, $defaultOrg, $org2, 'fn2', 'ln1');
        $this->createUser($manager, $defaultOrg, $org2, 'fn1', 'ln2');

        $manager->flush();
    }

    private function createOrganization(ObjectManager $manager, string $name): Organization
    {
        $organization = new Organization();
        $organization->setName($name);
        $organization->setEnabled(true);
        $manager->persist($organization);
        $this->addReference($organization->getName(), $organization);

        return $organization;
    }

    private function createUser(
        ObjectManager $manager,
        Organization $defaultOrg,
        Organization $organization,
        string $firstName,
        string $lastName
    ): void {
        $userName = 'segment.user.' . $firstName . '.' . $lastName;
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername($userName);
        $user->setPassword('password');
        $user->setEmail($userName . '@email.com');
        $user->setOrganization($defaultOrg);
        $user->addOrganization($organization);
        $manager->persist($user);
        $this->addReference($user->getUserIdentifier(), $user);
    }
}
