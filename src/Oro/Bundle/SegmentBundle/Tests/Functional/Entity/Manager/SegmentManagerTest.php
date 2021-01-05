<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SegmentManagerTest extends WebTestCase
{
    /** @var SegmentManager */
    private $manager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
            LoadUserData::class
        ]);

        $this->manager = self::getContainer()->get('oro_segment.segment_manager');
        $this->clearSegmentManagerCache();
    }

    protected function tearDown(): void
    {
        $this->clearSegmentManagerCache();
    }

    private function clearSegmentManagerCache(): void
    {
        self::getContainer()->get('oro_segment.segment_manager.cache')->deleteAll();
    }

    private function getEntityRepository(string $entityClass): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($entityClass);
    }

    private function getSegment(string $reference): Segment
    {
        return $this->getReference($reference);
    }

    public function testGetSegmentTypeChoices()
    {
        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);

        self::assertEquals([
            $dynamicSegment->getType()->getLabel() => $dynamicSegment->getType()->getName(),
            $staticSegment->getType()->getLabel() => $staticSegment->getType()->getName(),
        ], $this->manager->getSegmentTypeChoices());
    }

    public function testGetSegmentByEntityNameAndCheckOrder()
    {
        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        $dynamicSegmentWithFilter = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        $staticSegmentWithFilter = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        $staticSegmentWithSegmentFilter = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        $segmentWithFilter1 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER1);
        $segmentWithFilter2 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER);
        $segmentWithFilter3 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS);

        self::assertEqualsCanonicalizing(
            [
                'results' => [
                    [
                        'id' => 'segment_' . $dynamicSegment->getId(),
                        'text' => $dynamicSegment->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $dynamicSegmentWithFilter->getId(),
                        'text' => $dynamicSegmentWithFilter->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $staticSegment->getId(),
                        'text' => $staticSegment->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $staticSegmentWithFilter->getId(),
                        'text' => $staticSegmentWithFilter->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $staticSegmentWithSegmentFilter->getId(),
                        'text' => $staticSegmentWithSegmentFilter->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $segmentWithFilter1->getId(),
                        'text' => $segmentWithFilter1->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $segmentWithFilter2->getId(),
                        'text' => $segmentWithFilter2->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id' => 'segment_' . $segmentWithFilter3->getId(),
                        'text' => $segmentWithFilter3->getName(),
                        'type' => 'segment',
                    ]
                ],
                'more' => false
            ],
            $this->manager->getSegmentByEntityName(WorkflowAwareEntity::class, null)
        );
    }

    /**
     * @dataProvider caseSensitiveTermDataProvider
     *
     * @param string $segmentName
     */
    public function testGetSegmentByEntityNameWithCaseSensitiveTerm(string $segmentName): void
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        self::assertEquals(
            [
                'results' => [
                    [
                        'id' => 'segment_' . $segment->getId(),
                        'text' => $segment->getName(),
                        'type' => 'segment',
                    ],
                ],
                'more' => false
            ],
            $this->manager->getSegmentByEntityName(WorkflowAwareEntity::class, $segmentName)
        );
    }

    /**
     * @return array
     */
    public function caseSensitiveTermDataProvider(): array
    {
        return [
            'Default name' => [
                'Segment name' => 'Dynamic Segment with Filter',
            ],
            'Upped name' => [
                'Segment name' => 'dynamic segment with filter',
            ],
            'Lower name' => [
                'Segment name' => 'DYNAMIC SEGMENT WITH FILTER',
            ],
        ];
    }

    public function testFindById()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        self::assertEquals($segment, $this->manager->findById($segment->getId()));
    }

    public function testGetEntityQueryBuilder()
    {
        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        self::assertCount(50, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetEntityQueryBuilderWithSorting()
    {
        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        self::assertCount(0, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetFilterSubQueryDynamic()
    {
        $repository = $this->getEntityRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        $result = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        self::assertStringContainsString(
            'FROM Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            $result
        );
    }

    public function testGetFilterSubQueryDynamicWithLimit()
    {
        $repository = $this->getEntityRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        $dynamicSegment->setRecordsLimit(10);
        $dqlQuery = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        self::assertStringContainsString(
            'id FROM Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            $dqlQuery
        );

        $mainQb = $repository->createQueryBuilder('mainQuery');
        $mainQb->where(
            $mainQb->expr()->in('mainQuery.id', $dqlQuery)
        );

        $entities = $mainQb->getQuery()->getArrayResult();

        self::assertCount(10, $entities);
    }

    public function testGetFilterSubQueryStatic()
    {
        $repository = $this->getEntityRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        self::assertStringContainsString(
            'integerEntityId FROM Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot snp',
            $this->manager->getFilterSubQuery($dynamicSegment, $qb)
        );
    }

    public function testGetSegmentQueryBuilder()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);

        $qb = $this->manager->getSegmentQueryBuilder($segment);
        self::assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testGetSegmentQueryBuilderForSegmentWithDuplicateSegmentFilters()
    {
        $segment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS);

        $qb = $this->manager->getSegmentQueryBuilder($segment);
        self::assertInstanceOf(QueryBuilder::class, $qb);
        // Check that Query Builder may be converted to real SQL without errors.
        self::assertStringStartsWith('SELECT ', $qb->getQuery()->getSQL());
    }

    public function testGetSegmentQueryBuilderNotExistingType()
    {
        $segment = new Segment();
        $segment->setType(new SegmentType('NotExistingType'));

        $result = $this->manager->getSegmentQueryBuilder($segment);
        self::assertNull($result);
    }

    public function testFilterBySegmentWrongDefinition()
    {
        $segmentDefinition = ['Some wfong segment definition'];

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setEntity(User::class);
        $segment->setDefinition(json_encode($segmentDefinition));

        $repository = $this->getEntityRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $qb->addOrderBy($qb->expr()->asc('u.id'));
        $resultBeforeFilter = $qb->getQuery()->getResult();
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        self::assertEquals($resultBeforeFilter, $result);
    }

    public function testFilterBySegment()
    {
        $filteredUserFirstName = 'Elley';
        $segmentDefinition = [
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'Id',
                    'sorting' => 'DESC',
                    'func' => null
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'firstName',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => $filteredUserFirstName,
                            'type' => 1,
                        ]
                    ]
                ]
            ]
        ];

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setEntity(User::class);
        $segment->setDefinition(json_encode($segmentDefinition));

        $repository = $this->getEntityRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $qb->addOrderBy($qb->expr()->asc('u.id'));
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertEquals($filteredUserFirstName, reset($result)->getFirstName());
    }
}
