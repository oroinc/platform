<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private UserManager $userManager;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        UserManager $userManager
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->userManager = $userManager;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (User::class === $entityClass) {
            $user = new User();
            $user->setOrganization($repository->getReference('organization'));
            $user->setOwner($repository->getReference('business_unit'));
            $user->setUsername('johndoo');
            $user->setEmail('john@example.com');
            $user->setPassword($this->userManager->generatePassword());
            $user->setFirstName('John');
            $user->setMiddleName('M');
            $user->setLastName('Doo');
            $repository->setReference('user', $user);
            $this->userManager->updateUser($user, false);
            $em->flush();

            return ['user'];
        }

        if (Role::class === $entityClass) {
            $role = new Role();
            $role->setRole('ROLE_TEST');
            $role->setLabel('Test Role');
            $repository->setReference('role', $role);
            $em->persist($role);
            $em->flush();

            return ['role'];
        }

        if (Group::class === $entityClass) {
            $group = new Group();
            $group->setOrganization($repository->getReference('organization'));
            $group->setOwner($repository->getReference('business_unit'));
            $group->setName('Test Group');
            $repository->setReference('group', $group);
            $em->persist($group);
            $em->flush();

            return ['group'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (User::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'John Doo'
                : 'John M Doo';
        }
        if (Role::class === $entityClass) {
            return 'Test Role';
        }
        if (Group::class === $entityClass) {
            return 'Test Group';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
