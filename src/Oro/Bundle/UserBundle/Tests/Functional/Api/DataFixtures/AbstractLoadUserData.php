<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

abstract class AbstractLoadUserData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\UserBundle\Entity\UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        foreach ($this->getUsersData() as $userData) {
            $role = $userManager->getStorageManager()
                ->getRepository(Role::class)
                ->findOneBy(['role' => $userData['role']]);

            $group = $userManager->getStorageManager()
                ->getRepository(Group::class)
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
                ->addRole($role)
                ->addGroup($group)
                ->setEmail($userData['email'])
                ->setOrganization($organization)
                ->setOrganizations(new ArrayCollection([$organization]))
                ->addApiKey($api)
                ->setSalt('');

            $userManager->updateUser($user, false);
        }

        $userManager->getStorageManager()->flush();
    }

    /**
     * return array
     */
    abstract protected function getUsersData();
}
