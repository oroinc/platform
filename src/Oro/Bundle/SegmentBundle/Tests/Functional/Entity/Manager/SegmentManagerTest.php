<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SegmentManagerTest extends WebTestCase
{
    private const SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER_SQL_QUERY =
        'SELECT t0_.id AS id_0, t0_.id AS id_1'
        . ' FROM test_workflow_aware_entity t0_'
        . ' WHERE t0_.id > ?'
        . ' AND t0_.id IN ('
        . 'SELECT t1_.id FROM test_workflow_aware_entity t1_ WHERE t1_.id > ?)';

    private const SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS_SQL_QUERY =
        'SELECT t0_.id AS id_0, t0_.id AS id_1'
        . ' FROM test_workflow_aware_entity t0_'
        . ' WHERE t0_.id > ?'
        . ' AND (t0_.id IN ('
        . 'SELECT t1_.id FROM test_workflow_aware_entity t1_ WHERE t1_.id > ? AND t1_.id IN ('
        . 'SELECT t2_.id FROM test_workflow_aware_entity t2_ WHERE t2_.id > ?)))'
        . ' AND t0_.id IN ('
        . 'SELECT t3_.id FROM test_workflow_aware_entity t3_ WHERE t3_.id > ?)'
        . ' AND t0_.id IN ('
        . 'SELECT t4_.id FROM test_workflow_aware_entity t4_ WHERE t4_.id > ?)'
        . ' AND (t0_.id IN ('
        . 'SELECT t5_.id FROM test_workflow_aware_entity t5_ WHERE t5_.id > ? AND t5_.id IN ('
        . 'SELECT t6_.id FROM test_workflow_aware_entity t6_ WHERE t6_.id > ?)))'
        . ' AND t0_.id IN ('
        . 'SELECT t7_.id FROM test_workflow_aware_entity t7_ WHERE t7_.id > ?)';

    /** @var SegmentManager */
    private $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
            LoadUserData::class
        ]);

        $this->manager = self::getContainer()->get('oro_segment.segment_manager');
        $this->clearSegmentManagerCache();
    }

    protected function tearDown()
    {
        if ($this->getFilterExecutionContext()->isValidationEnabled()) {
            $this->getFilterExecutionContext()->disableValidation();
        }
        $this->clearSegmentManagerCache();
    }

    private function clearSegmentManagerCache(): void
    {
        self::getContainer()->get('oro_segment.segment_manager.cache')->deleteAll();
    }

    private function clearSegmentManagerCacheStats(): void
    {
        $cache = $this->getSegmentManagerCache();
        // unfortunately the reflection is only way to reset the cache stats
        $hitsCountProperty = ReflectionUtil::getProperty(new \ReflectionClass($cache), 'hitsCount');
        $hitsCountProperty->setAccessible(true);
        $hitsCountProperty->setValue($cache, 0);
        $missesCountProperty = ReflectionUtil::getProperty(new \ReflectionClass($cache), 'missesCount');
        $missesCountProperty->setAccessible(true);
        $missesCountProperty->setValue($cache, 0);
    }

    private function getSegmentManagerCache(): ArrayCache
    {
        $cache = self::getContainer()->get('oro_segment.segment_manager.cache');
        self::assertInstanceOf(ArrayCache::class, $cache, 'These tests can work only with ArrayCache.');

        return $cache;
    }

    private function getFilterExecutionContext(): FilterExecutionContext
    {
        return self::getContainer()->get('oro_filter.execution_context');
    }

    private function enableFilterValidation(bool $enable): void
    {
        if ($enable) {
            $this->getFilterExecutionContext()->enableValidation();
        }
    }

    private function assertGetSegmentQueryBuilder(
        Segment $segment,
        string $expectedSql = null,
        array $firstTryCacheStats = null,
        array $secondTryCacheStats = null
    ): void {
        $this->clearSegmentManagerCacheStats();
        $qb = $this->manager->getSegmentQueryBuilder($segment);
        $sql = $qb->getQuery()->getSQL();
        if ($expectedSql) {
            self::assertEquals($expectedSql, $sql, 'SQL - First Try');
        }
        if ($firstTryCacheStats) {
            self::assertEquals(
                $firstTryCacheStats,
                array_intersect_key($this->getSegmentManagerCache()->getStats(), $firstTryCacheStats),
                'Cache Stats - First Try'
            );
        }

        // do get the segment query builder one more time to ensure that it returns the same query
        $this->clearSegmentManagerCacheStats();
        $qb = $this->manager->getSegmentQueryBuilder($segment);
        self::assertEquals($sql, $qb->getQuery()->getSQL(), 'SQL - Second Try');
        if ($secondTryCacheStats) {
            self::assertEquals(
                $secondTryCacheStats,
                array_intersect_key($this->getSegmentManagerCache()->getStats(), $secondTryCacheStats),
                'Cache Stats - Second Try'
            );
        }
    }

    public function filterExecutionContextDataProvider(): array
    {
        return [
            ['enableFilterValidation' => false],
            ['enableFilterValidation' => true]
        ];
    }

    public function testGetSegmentTypeChoices()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $this->assertEquals([
            $dynamicSegment->getType()->getLabel() => $dynamicSegment->getType()->getName(),
            $staticSegment->getType()->getLabel() => $staticSegment->getType()->getName(),
        ], $this->manager->getSegmentTypeChoices());
    }

    public function testGetSegmentByEntityNameAndCheckOrder()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        /** @var Segment $dynamicSegment */
        $dynamicSegmentWithFilter = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        /** @var Segment $staticSegmentWithFilter */
        $staticSegmentWithFilter = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        /** @var Segment $staticSegmentWithSegmentFilter */
        $staticSegmentWithSegmentFilter = $this->getReference(LoadSegmentData::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        /** @var Segment $segmentWithFilter1 */
        $segmentWithFilter1 = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER1);
        /** @var Segment $segmentWithFilter2 */
        $segmentWithFilter2 = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER);
        /** @var Segment $segmentWithFilter3 */
        $segmentWithFilter3 = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS);

        $this->assertEqualsCanonicalizing(
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
        /** @var Segment $dynamicSegment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $this->assertEquals(
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
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $this->assertEquals($segment, $this->manager->findById($segment->getId()));
    }

    public function testGetEntityQueryBuilder()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $this->assertCount(50, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetEntityQueryBuilderWithSorting()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $this->assertCount(0, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetFilterSubQueryDynamic()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $result = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        $this->assertContains('FROM Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity', $result);
    }

    public function testGetFilterSubQueryDynamicWithLimit()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $dynamicSegment->setRecordsLimit(10);
        $dqlQuery = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        $this->assertContains(
            'id FROM Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            $dqlQuery
        );

        $mainQb = $repository->createQueryBuilder('mainQuery');
        $mainQb->where(
            $mainQb->expr()->in('mainQuery.id', $dqlQuery)
        );

        $entities = $mainQb->getQuery()->getArrayResult();

        $this->assertCount(10, $entities);
    }

    public function testGetFilterSubQueryStatic()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $this->assertContains(
            'integerEntityId FROM Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot snp',
            $this->manager->getFilterSubQuery($dynamicSegment, $qb)
        );
    }

    /**
     * @dataProvider filterExecutionContextDataProvider
     */
    public function testGetSegmentQueryBuilder(bool $enableFilterValidation)
    {
        $this->enableFilterValidation($enableFilterValidation);

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER)
        );
    }

    /**
     * @dataProvider filterExecutionContextDataProvider
     */
    public function testGetSegmentQueryBuilderForSegmentWithSegmentFilter(bool $enableFilterValidation)
    {
        $this->enableFilterValidation($enableFilterValidation);

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER),
            self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER_SQL_QUERY,
            ['hits' => 0, 'misses' => 1],
            ['hits' => 1, 'misses' => 0]
        );
    }

    /**
     * @dataProvider filterExecutionContextDataProvider
     */
    public function testGetSegmentQueryBuilderForSegmentWithDuplicateSegmentFilters(bool $enableFilterValidation)
    {
        $this->enableFilterValidation($enableFilterValidation);

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS),
            self::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS_SQL_QUERY,
            ['hits' => 0, 'misses' => 1],
            ['hits' => 1, 'misses' => 0]
        );
    }

    /**
     * @dataProvider filterExecutionContextDataProvider
     */
    public function testGetSegmentQueryBuilderForSegmentWithSegmentAndWithDuplicateFilter(bool $enableFilterValidation)
    {
        $this->enableFilterValidation($enableFilterValidation);

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER),
            self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER_SQL_QUERY,
            ['hits' => 0, 'misses' => 1],
            ['hits' => 1, 'misses' => 0]
        );

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS),
            self::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS_SQL_QUERY,
            ['hits' => 0, 'misses' => 1],
            ['hits' => 1, 'misses' => 0]
        );
    }

    /**
     * @dataProvider filterExecutionContextDataProvider
     */
    public function testGetSegmentQueryBuilderForSegmentWithDuplicateAndWithSegmentFilter(bool $enableFilterValidation)
    {
        $this->enableFilterValidation($enableFilterValidation);

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS),
            self::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS_SQL_QUERY,
            ['hits' => 0, 'misses' => 1],
            ['hits' => 1, 'misses' => 0]
        );

        $this->assertGetSegmentQueryBuilder(
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER),
            self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER_SQL_QUERY,
            ['hits' => 1, 'misses' => 0],
            ['hits' => 1, 'misses' => 0]
        );
    }

    public function testGetSegmentQueryBuilderNotExistingType()
    {
        $segment = new Segment();
        $segment->setType(new SegmentType('NotExistingType'));

        $result = $this->manager->getSegmentQueryBuilder($segment);
        $this->assertNull($result);
    }

    public function testFilterBySegmentWrongDefinition()
    {
        $segmentDefinition = ['Some wfong segment definition'];

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setEntity(User::class);
        $segment->setDefinition(json_encode($segmentDefinition));

        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $qb->addOrderBy($qb->expr()->asc('u.id'));
        $resultBeforeFilter = $qb->getQuery()->getResult();
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        $this->assertEquals($resultBeforeFilter, $result);
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

        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $qb->addOrderBy($qb->expr()->asc('u.id'));
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($filteredUserFirstName, reset($result)->getFirstName());
    }
}
