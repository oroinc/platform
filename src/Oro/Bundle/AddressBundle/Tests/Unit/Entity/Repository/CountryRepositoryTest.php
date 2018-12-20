<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;

class CountryRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var CountryRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->repository = new CountryRepository($this->entityManager, new ClassMetadata(Country::class));
    }

    public function testGetCountries()
    {
        $countries = [
            new Country('iso2Code1'),
            new Country('iso2Code2'),
        ];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())->method('setHint')->with(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            TranslationWalker::class
        );
        $query->expects($this->once())->method('execute')->willReturn($countries);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('c')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Country::class, 'c')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('c.name', 'ASC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertSame($countries, $this->repository->getCountries());
    }
}
