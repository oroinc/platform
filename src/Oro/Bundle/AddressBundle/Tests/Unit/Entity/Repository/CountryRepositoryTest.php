<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

class CountryRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private CountryRepository $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnMap([
                [Country::class, new ClassMetadata(Country::class)],
            ]);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Country::class, $this->entityManager],
            ]);

        $this->repository = new CountryRepository($managerRegistry, Country::class);
    }

    public function testGetCountries(): void
    {
        $countries = [
            new Country('iso2Code1'),
            new Country('iso2Code2'),
        ];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('setHint')
            ->with(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                TranslatableSqlWalker::class
            );
        $query->expects(self::once())
            ->method('execute')
            ->willReturn($countries);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('c')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Country::class, 'c')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('c.name', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        self::assertSame($countries, $this->repository->getCountries());
    }
}
