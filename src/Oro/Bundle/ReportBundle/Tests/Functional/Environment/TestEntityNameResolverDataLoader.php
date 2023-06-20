<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\UserBundle\Entity\User;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Report::class === $entityClass) {
            $report = new Report();
            $report->setOrganization($repository->getReference('organization'));
            $report->setOwner($repository->getReference('business_unit'));
            $report->setEntity(User::class);
            $report->setDefinition('{}');
            $report->setName('Test Report');
            $repository->setReference('report', $report);
            $em->persist($report);
            $em->flush();

            return ['report'];
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
        if (Report::class === $entityClass) {
            return 'Test Report';
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
