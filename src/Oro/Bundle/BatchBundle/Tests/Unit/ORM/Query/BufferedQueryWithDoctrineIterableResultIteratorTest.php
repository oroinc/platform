<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BufferedQueryWithDoctrineIterableResultIteratorTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testCountMethod(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
        ];
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) use (&$records, &$actualSql) {
                $actualSql = $sql;

                return $this->createFetchStatementMock([['sclr_0' => count($records)]]);
            });

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);

        $this->assertEquals(count($records), $iterator->count());
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSql
        );
    }

    public function testCountMethodWithExplicitlySetBufferSize(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
        ];
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) use (&$records, &$actualSql) {
                $actualSql = $sql;

                return $this->createFetchStatementMock([['sclr_0' => count($records)]]);
            });

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(1);

        $this->assertEquals(count($records), $iterator->count());
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSql
        );
    }

    public function testCountMethodWithWithMaxResultsSource(): void
    {
        $maxResults = 2;
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) use (&$maxResults, &$actualSql) {
                $actualSql = $sql;

                return $this->createCountStatementMock($maxResults);
            });

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);

        $this->assertEquals($maxResults, $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . $maxResults
            . ') AS count_query',
            $actualSql
        );
    }

    public function testCountMethodWithMaxResultsSourceAndExplicitlySetBufferSize(): void
    {
        $maxResults = 2;
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) use (&$maxResults, &$actualSql) {
                $actualSql = $sql;

                return $this->createCountStatementMock($maxResults);
            });

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(1);

        $this->assertEquals($maxResults, $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . $maxResults
            . ') AS count_query',
            $actualSql
        );
    }

    public function testIteratorWithDefaultParameters(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createFetchStatementMock([['sclr_0' => count($records)]]),
            $this->createFetchStatementMock([$records[0], $records[1], $records[2]])
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);

        // total count must be calculated once
        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$count]['a_0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE . '',
            $actualSqls[1]
        );
    }

    private function mockQuery(array &$statements, int &$statementCounter, ?array &$actualSqls): void
    {
        $this->getDriverConnectionMock($this->em)
            ->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                $actualSqls[$statementCounter] = $sql;
                $statement = $statements[$statementCounter];
                $statementCounter++;

                return $statement;
            });
    }

    public function testIteratorWithMaxResultsSource(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];
        $maxResults = 2;
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock($maxResults),
            $this->createFetchStatementMock([$records[0], $records[1]]),
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$count]['a_0'], $record->a);
            $count++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(2, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . $maxResults
            . ') AS count_query',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2',
            $actualSqls[1]
        );
    }

    public function testIteratorWithMaxResultsSourceAndExplicitlySetBufferSize(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
            ['a_0' => '4'],
        ];
        $maxResults = 3;
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock($maxResults),
            $this->createFetchStatementMock([$records[0], $records[1]]),
            $this->createFetchStatementMock([$records[2]])
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(2);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$count]['a_0'], $record->a);
            $count++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . $maxResults
            . ') AS count_query',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2',
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2 OFFSET 2',
            $actualSqls[2]
        );
    }

    public function testIteratorWithMaxResultsSourceAndFirstResultAndExplicitlySetBufferSize(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
            ['a_0' => '4'],
        ];
        $firstResult = 1;
        $maxResults = 3;
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock($maxResults),
            $this->createFetchStatementMock([$records[1], $records[2]]),
            $this->createFetchStatementMock([$records[3]])
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o')
            ->setMaxResults($maxResults)
            ->setFirstResult($firstResult);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(2);

        $count = 0;
        $index = $firstResult;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$index]['a_0'], $record->a);
            $count++;
            $index++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . $maxResults . ' OFFSET ' . $firstResult . ') AS count_query',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2 OFFSET ' . $firstResult,
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2 OFFSET '
            . ($firstResult + $maxResults - 1),
            $actualSqls[2]
        );
    }

    public function testIteratorWithObjectHydrationMode(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createFetchStatementMock([['sclr_0' => count($records)]]),
            $this->createFetchStatementMock([$records[0], $records[1]]),
            $this->createFetchStatementMock([$records[2]])
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setHydrationMode(Query::HYDRATE_OBJECT);
        $iterator->setBufferSize(2);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$count]['a_0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2',
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2 OFFSET 2',
            $actualSqls[2]
        );
    }

    public function testIteratorWithArrayHydrationMode(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createFetchStatementMock([['sclr_0' => count($records)]]),
            $this->createFetchStatementMock([$records[0], $records[1], $records[2]]),
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertEquals($records[$count]['a_0'], $record['a']);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT '
            . BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE . '',
            $actualSqls[1]
        );
    }

    public function testIteratorInReverseDirection(): void
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createFetchStatementMock([['sclr_0' => count($records)]]),
            $this->createFetchStatementMock([$records[0], $records[1]]),
            $this->createFetchStatementMock([$records[2]])
        ];

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setReverse(true);
        $iterator->setBufferSize(2);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf(Entity::class, $record);
            $this->assertEquals($records[$count]['a_0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2 OFFSET 2',
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ ORDER BY e0_.a ASC LIMIT 2',
            $actualSqls[2]
        );
    }

    /**
     * @dataProvider pageCallbackDataProvider
     */
    public function testPageCallback(array $statements, int $bufferSize, int $expectedPages): void
    {
        $statementCounter = 0;

        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = (new BufferedQueryResultIterator($source))
            ->setBufferSize($bufferSize);
        $pages = 0;
        $iterator->setPageCallback(static function () use (&$pages) {
            $pages++;
        });

        $this->assertEquals(0, $pages);
        iterator_to_array($iterator);
        $this->assertEquals($expectedPages, $pages);
    }

    public function pageCallbackDataProvider(): array
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];

        return [
            [
                'statements' => [$this->createFetchStatementMock([['sclr_0' => 0]])],
                'bufferSize' => 1,
                'pages' => 0,
            ],
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0]]),
                    $this->createFetchStatementMock([$records[1]]),
                    $this->createFetchStatementMock([$records[2]]),
                ],
                'bufferSize' => 1,
                'pages' => 3,
            ],
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0], $records[1]]),
                    $this->createFetchStatementMock([$records[2]]),
                ],
                'bufferSize' => 2,
                'pages' => 2,
            ],
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0], $records[1], $records[2]]),
                ],
                'bufferSize' => 3,
                'pages' => 1,
            ],
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0], $records[1], $records[2]]),
                ],
                'bufferSize' => 5,
                'pages' => 1,
            ],
        ];
    }

    /**
     * @dataProvider pageLoadedCallbackProvider
     */
    public function testPageLoadedCallback(array $statements, array $expectedResult, callable $pageLoadedCallback): void
    {
        $statementCounter = 0;
        $this->mockQuery($statements, $statementCounter, $actualSqls);

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Entity::class, 'o');

        $iterator = (new BufferedQueryResultIterator($source))
            ->setBufferSize(1)
            ->setPageLoadedCallback($pageLoadedCallback);

        $this->assertEquals($expectedResult, iterator_to_array($iterator));
    }

    public function pageLoadedCallbackProvider(): array
    {
        $records = [
            ['a_0' => '1'],
            ['a_0' => '2'],
            ['a_0' => '3'],
        ];

        return [
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0]]),
                    $this->createFetchStatementMock([$records[1]]),
                    $this->createFetchStatementMock([$records[2]]),
                ],
                'expectedResult' => [
                    new Entity(1),
                    new Entity(2),
                    new Entity(3),
                ],
                static function (array $rows) {
                    return $rows;
                },
            ],
            [
                'statements' => [
                    $this->createFetchStatementMock([['sclr_0' => count($records)]]),
                    $this->createFetchStatementMock([$records[0]]),
                    $this->createFetchStatementMock([$records[1]]),
                    $this->createFetchStatementMock([$records[2]]),
                ],
                'expectedResult' => [
                    ['entity' => new Entity(1), '_id' => 1],
                    ['entity' => new Entity(2), '_id' => 2],
                    ['entity' => new Entity(3), '_id' => 3],
                ],
                static function (array $rows) {
                    return array_map(
                        static function (Entity $entity) {
                            return ['entity' => $entity, '_id' => $entity->a];
                        },
                        $rows
                    );
                },
            ],
        ];
    }
}
