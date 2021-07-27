<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

abstract class AbstractLoadUserData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadBusinessUnit::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\UserBundle\Entity\UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        foreach ($this->getUsersData() as $userData) {
            $role = $manager->getRepository(Role::class)
                ->findOneBy(['role' => $userData['role']]);

            $group = $manager->getRepository(Group::class)
                ->findOneBy(['name' => $userData['group']]);

            /** @var User $user */
            $user = $userManager->createUser();
            $organization = $manager->getRepository(Organization::class)->getFirst();

            $api = new UserApi();
            $api->setApiKey($userData['apiKey'])
                ->setOrganization($organization)
                ->setUser($user);

            $user
                ->setUsername($userData['username'])
                ->setPlainPassword($userData['plainPassword'])
                ->setFirstName($userData['firstName'])
                ->setLastName($userData['lastName'])
                ->addUserRole($role)
                ->addGroup($group)
                ->setEmail($userData['email'])
                ->setOwner($this->getReference('business_unit'))
                ->setOrganization($organization)
                ->setOrganizations(new ArrayCollection([$organization]))
                ->addApiKey($api)
                ->setSalt('');

            $userManager->updateUser($user, false);

            $this->setReference($userData['reference'], $user);
        }

        $manager->flush();
    }

    /**
     * return array
     */
    abstract protected function getUsersData();
}
