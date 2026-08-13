<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadDifferentOwnerEntitiesData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const ADMIN_OWNED_ENTITY = 'admin_owned_entity';
    public const SAME_BUSINESS_UNIT_OWNED_ENTITY = 'same_business_unit_owned_entity';
    public const ANOTHER_BUSINESS_UNIT_OWNED_ENTITY = 'another_business_unit_owned_entity';

    public const SAME_BUSINESS_UNIT_USER = 'same_business_unit_user';
    public const ANOTHER_BUSINESS_UNIT_USER = 'another_business_unit_user';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class, LoadBusinessUnitData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(LoadUser::USER);
        /** @var BusinessUnit $anotherBusinessUnit */
        $anotherBusinessUnit = $this->getReference('TestBusinessUnit');

        $owners = [
            self::ADMIN_OWNED_ENTITY => $admin,
            self::SAME_BUSINESS_UNIT_OWNED_ENTITY => $this->createUser(
                $manager,
                self::SAME_BUSINESS_UNIT_USER,
                $admin->getOwner()
            ),
            self::ANOTHER_BUSINESS_UNIT_OWNED_ENTITY => $this->createUser(
                $manager,
                self::ANOTHER_BUSINESS_UNIT_USER,
                $anotherBusinessUnit
            )
        ];

        foreach ($owners as $reference => $owner) {
            $entity = new TestEntityWithUserOwnership();
            $entity->setName($reference);
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setOwner($owner);
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $username, BusinessUnit $businessUnit): User
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $userManager = $this->container->get('oro_user.manager');
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername($username)
            ->setPlainPassword($username)
            ->setEmail($username . '@example.com')
            ->setFirstName($username)
            ->setLastName($username)
            ->setOwner($businessUnit)
            ->addBusinessUnit($businessUnit)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]))
            ->setEnabled(true);
        $userManager->updateUser($user);

        $this->setReference($username, $user);

        return $user;
    }
}
