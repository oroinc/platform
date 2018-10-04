<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;

class RegionRepositoryTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_NAME = 'RegionEntityName';

    /**
     * @var RegionRepository
     */
    protected $repository;

    /**
     * @var TranslatableListener|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translatableListener;

    /**
     * @var EventManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventManager;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $testRegions = ['one', 'two', 'three'];

    protected function setUp()
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

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'execute'])
            ->getMockForAbstractClass();
        $query->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                ],
                [
                    TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                    $locale
                ]
            );
        $query->expects($this->once())->method('execute')
            ->will($this->returnValue($this->testRegions));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'orderBy', 'setParameter', 'getQuery'])
            ->getMock();
        $queryBuilder->expects($this->once())->method('select')->with($entityAlias)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('from')->with(self::ENTITY_NAME, $entityAlias)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')->with('r.country = :country')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('orderBy')->with('r.name', 'ASC')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')->with('country', $country)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $actualRegions = $this->repository->getCountryRegions($country);
        $this->assertEquals($this->testRegions, $actualRegions);
    }

    /**
     * @expectedException \Gedmo\Exception\RuntimeException
     * @expectedExceptionMessage The translation listener could not be found
     */
    public function testGetCountryRegionsException()
    {
        $country = new Country('iso2Code');

        $this->eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[]]);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'execute'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('setHint')
            ->with(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'orderBy', 'setParameter', 'getQuery'])
            ->getMock();
        $queryBuilder->expects($this->once())->method('select')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')
            ->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->repository->getCountryRegions($country);
    }
}
