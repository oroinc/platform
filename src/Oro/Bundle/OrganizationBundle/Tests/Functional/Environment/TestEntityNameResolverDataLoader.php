<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Organization::class === $entityClass) {
            $account = new Organization();
            $account->setName('Test Organization');
            $repository->setReference('testOrganization', $account);
            $em->persist($account);
            $em->flush();

            return ['testOrganization'];
        }

        if (BusinessUnit::class === $entityClass) {
            $account = new BusinessUnit();
            $account->setOrganization($repository->getReference('organization'));
            $account->setOwner($repository->getReference('business_unit'));
            $account->setName('Test Business Unit');
            $repository->setReference('testBusinessUnit', $account);
            $em->persist($account);
            $em->flush();

            return ['testBusinessUnit'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Organization::class === $entityClass) {
            return 'Test Organization';
        }
        if (BusinessUnit::class === $entityClass) {
            return 'Test Business Unit';
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
