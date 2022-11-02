<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUsersData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadOrganization::class, LoadBusinessUnit::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $this->getReference('organization');
        $businessUnit = $this->getReference('business_unit');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $businessUnit1 = new BusinessUnit();
        $businessUnit1->setOwner($businessUnit);
        $businessUnit1->setOrganization($organization);
        $businessUnit1->setName('subordinate BU');
        $manager->persist($businessUnit1);

        $businessUnit2 = new BusinessUnit();
        $businessUnit2->setOrganization($organization);
        $businessUnit2->setName('root BU');
        $manager->persist($businessUnit2);

        $manager->flush();

        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername('subordinate_bu_user')
            ->setOwner($businessUnit)
            ->setPlainPassword('subordinate_bu_user')
            ->setEmail('subordinate_bu_user@test.com')
            ->setFirstName('Mike')
            ->setLastName('Doe')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($businessUnit1)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);
        $this->setReference('subordinate_bu_user', $user);

        /** @var User $user */
        $user1 = $userManager->createUser();
        $user1->setUsername('default_bu_user')
            ->setOwner($businessUnit)
            ->setPlainPassword('default_bu_user')
            ->setEmail('default_bu_user@test.com')
            ->setFirstName('Rob')
            ->setLastName('Doe')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($businessUnit)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user1);
        $this->setReference('default_bu_user', $user1);

        /** @var User $user */
        $user2 = $userManager->createUser();
        $user2->setUsername('root_bu_user')
            ->setOwner($businessUnit)
            ->setPlainPassword('root_bu_user')
            ->setEmail('root_bu_user@test.com')
            ->setFirstName('Kris')
            ->setLastName('Doe')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($businessUnit2)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user2);
        $this->setReference('root_bu_user', $user2);
    }
}
