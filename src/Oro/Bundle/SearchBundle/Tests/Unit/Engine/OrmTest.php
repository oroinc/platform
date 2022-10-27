<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\SearchBundle\Query\LazyResult;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrmTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS = 'Stub\TestEntity';
    private const TEST_ALIAS = 'test_entity';

    /** @var SearchIndexRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var Orm */
    private $engine;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SearchIndexRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with('OroSearchBundle:Item')
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroSearchBundle:Item')
            ->willReturn($manager);

        $this->mapper = $this->createMock(ObjectMapper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->engine = new Orm($registry, $this->mapper, $this->eventDispatcher);
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $response, array $items, int $count, array $aggregatedData = [])
    {
        $query = new Query();

        $entityConfiguration = [
            'alias' => self::TEST_ALIAS,
            'fields' => [['name' => 'property', 'target_type' => 'text']]
        ];

        $this->mapper->expects($this->any())
            ->method('getEntityConfig')
            ->with(self::TEST_CLASS)
            ->willReturn($entityConfiguration);
        $this->mapper->expects($this->any())
            ->method('mapSelectedData')
            ->willReturn([]);

        $this->repository->expects($this->any())
            ->method('search')
            ->with($query)
            ->willReturn($response);
        $this->repository->expects($this->any())
            ->method('getRecordsCount')
            ->with($query)
            ->willReturn($count);
        $this->repository->expects($this->any())
            ->method('getAggregatedData')
            ->with($query)
            ->willReturn($aggregatedData);

        $expectedItems = [];
        foreach ($items as $item) {
            $expectedItems[] = new Item(
                $item['class'],
                $item['id'],
                null,
                [],
                $entityConfiguration
            );
        }

        $result = $this->engine->search($query);

        $this->assertInstanceOf(LazyResult::class, $result);
        $this->assertEquals($query, $result->getQuery());

        $this->assertEquals($expectedItems, $result->getElements());
        $this->assertEquals($count, $result->getRecordsCount());
        $this->assertEquals($aggregatedData, $result->getAggregatedData());
    }

    public function searchDataProvider(): array
    {
        return [
            'valid response' => [
                'response' => [
                    [
                        'item' => [
                            'entity' => self::TEST_CLASS,
                            'recordId' => 1,
                            'title' => null,
                        ],
                    ],
                    [
                        'item' => [
                            'entity' => self::TEST_CLASS,
                            'recordId' => 2,
                            'title' => null,
                        ],
                    ]
                ],
                'items' => [
                    ['class' => self::TEST_CLASS, 'id' => 1],
                    ['class' => self::TEST_CLASS, 'id' => 2],
                ],
                'count' => 5,
                'aggregatedData' => [
                    'sum_field' => 42,
                    'count_filed' => [
                        'firstValue' => 1001,
                        'secondValue' => 2002,
                    ]
                ]
            ],
            'empty response' => [
                'response' => [],
                'items' => [],
                'count' => 0
            ]
        ];
    }
}
