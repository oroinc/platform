<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

abstract class AbstractLoadUserData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadBusinessUnit::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        foreach ($this->getUsersData() as $userData) {
            $role = $manager->getRepository(Role::class)->findOneBy(['role' => $userData['role']]);
            /** @var User $user */
            $user = $userManager->createUser();
            /** @var Organization $organization */
            $organization = $this->getReference(LoadOrganization::ORGANIZATION);

            $api = new UserApi();
            $api->setApiKey($userData['apiKey'])
                ->setOrganization($organization)
                ->setUser($user);
            $user
                ->setUsername($userData['username'])
                ->setPlainPassword($userData['plainPassword'])
                ->setFirstName($userData['firstName'])
                ->setLastName($userData['lastName'])
                ->addGroup($manager->getRepository(Group::class)->findOneBy(['name' => $userData['group']]))
                ->setEmail($userData['email'])
                ->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT))
                ->setOrganization($organization)
                ->setOrganizations(new ArrayCollection([$organization]))
                ->addApiKey($api)
                ->setSalt('');
            if ($role) {
                $user->addUserRole($role);
            }

            $userManager->updateUser($user, false);
            $this->setReference($userData['reference'], $user);
        }
        $manager->flush();
    }

    abstract protected function getUsersData(): array;
}
