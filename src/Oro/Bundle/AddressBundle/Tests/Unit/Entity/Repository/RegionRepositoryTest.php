<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\EventManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\RuntimeException;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

class RegionRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_NAME = 'RegionEntityName';

    /** @var RegionRepository */
    private $repository;

    /** @var TranslatableListener|\PHPUnit\Framework\MockObject\MockObject */
    private $translatableListener;

    /** @var EventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $eventManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var array */
    private $testRegions = ['one', 'two', 'three'];

    protected function setUp(): void
    {
        $this->translatableListener = $this->createMock(TranslatableListener::class);
        $this->eventManager = $this->createMock(EventManager::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects($this->any())
            ->method('getEventManager')
            ->willReturn($this->eventManager);

        $this->repository = new RegionRepository($this->entityManager, new ClassMetadata(self::ENTITY_NAME));
    }

    /**
     * Tests both getCountryRegionsQueryBuilder and getCountryRegions
     */
    public function testGetCountryRegions()
    {
        $entityAlias = 'r';
        $country = new Country('iso2Code');
        $locale = 'de_DE';

        $this->translatableListener->expects($this->once())
            ->method('getListenerLocale')
            ->willReturn($locale);

        $this->eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[$this->translatableListener]]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    TranslatableSqlWalker::class
                ],
                [
                    TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                    $locale
                ]
            );
        $query->expects($this->once())->method('execute')
            ->willReturn($this->testRegions);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with($entityAlias)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('from')
            ->with(self::ENTITY_NAME, $entityAlias)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('r.country = :country')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.name', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('country', $country)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $actualRegions = $this->repository->getCountryRegions($country);
        $this->assertEquals($this->testRegions, $actualRegions);
    }

    public function testGetCountryRegionsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The translation listener could not be found');

        $country = new Country('iso2Code');

        $this->eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[]]);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('setHint')
            ->with(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                TranslatableSqlWalker::class
            );

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->getCountryRegions($country);
    }
}
