<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Doctrine\ORM\Query;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

class BufferedQueryResultIteratorTest extends OrmTestCase
{
    /**
     * @var EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'Stub' => 'Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub'
            )
        );
    }

    public function testCountMethod()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
        ];
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$records, &$actualSql) {
                        $actualSql = $sql;
                        return $this->createCountStatementMock(count($records));
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);

        $this->assertEquals(count($records), $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_) AS e',
            $actualSql
        );
    }

    public function testCountMethodWithExplicitlySetBufferSize()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
        ];
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$records, &$actualSql) {
                        $actualSql = $sql;
                        return $this->createCountStatementMock(count($records));
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(1);

        $this->assertEquals(count($records), $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_) AS e',
            $actualSql
        );
    }

    public function testCountMethodWithWithMaxResultsSource()
    {
        $maxResults = 2;
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$maxResults, &$actualSql) {
                        $actualSql = $sql;
                        return $this->createCountStatementMock($maxResults);
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);

        $this->assertEquals($maxResults, $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT ' . $maxResults . ') AS e',
            $actualSql
        );
    }

    public function testCountMethodWithMaxResultsSourceAndExplicitlySetBufferSize()
    {
        $maxResults = 2;
        $actualSql = '';

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$maxResults, &$actualSql) {
                        $actualSql = $sql;
                        return $this->createCountStatementMock($maxResults);
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(1);

        $this->assertEquals($maxResults, $iterator->count());
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT ' . $maxResults . ') AS e',
            $actualSql
        );
    }

    public function testIteratorWithDefaultParameters()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock(count($records)),
            $this->createFetchStatementMock([$records[0], $records[1], $records[2]])
        ];

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);

        // total count must be calculated once
        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_) AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT '
            . BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE . ' OFFSET 0',
            $actualSqls[1]
        );
    }

    public function testIteratorWithMaxResultsSource()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];
        $maxResults = 2;
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock($maxResults),
            $this->createFetchStatementMock([$records[0], $records[1]]),
        ];

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(2, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT ' . $maxResults . ') AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET 0',
            $actualSqls[1]
        );
    }

    public function testIteratorWithMaxResultsSourceAndExplicitlySetBufferSize()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
            ['a0' => '4'],
        ];
        $maxResults = 3;
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock($maxResults),
            $this->createFetchStatementMock([$records[0], $records[1]]),
            $this->createFetchStatementMock([$records[2]])
        ];

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults($maxResults);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(2);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT ' . $maxResults . ') AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET 0',
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET 2',
            $actualSqls[2]
        );
    }

    public function testIteratorWithMaxResultsSourceAndFirstResultAndExplicitlySetBufferSize()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
            ['a0' => '4'],
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults($maxResults)
            ->setFirstResult($firstResult);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(2);

        $count = 0;
        $index = $firstResult;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$index]['a0'], $record->a);
            $count++;
            $index++;
        }
        $this->assertEquals($maxResults, $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT '
            . $maxResults . ' OFFSET ' . $firstResult . ') AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET ' . $firstResult,
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET ' . ($firstResult + $maxResults - 1),
            $actualSqls[2]
        );
    }

    public function testIteratorWithObjectHydrationMode()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock(count($records)),
            $this->createFetchStatementMock([$records[0], $records[1]]),
            $this->createFetchStatementMock([$records[2]])
        ];

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setHydrationMode(Query::HYDRATE_OBJECT);
        $iterator->setBufferSize(2);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertCount(3, $actualSqls);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_) AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET 0',
            $actualSqls[1]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT 2 OFFSET 2',
            $actualSqls[2]
        );
    }

    public function testIteratorWithArrayHydrationMode()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];
        $actualSqls = [];
        $statementCounter = 0;
        $statements = [
            $this->createCountStatementMock(count($records)),
            $this->createFetchStatementMock([$records[0], $records[1], $records[2]]),
        ];

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->returnCallback(
                    function ($sql) use (&$statements, &$statementCounter, &$actualSqls) {
                        $actualSqls[$statementCounter] = $sql;
                        $statement = $statements[$statementCounter];
                        $statementCounter++;
                        return $statement;
                    }
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertEquals($records[$count]['a0'], $record['a']);
            $count++;
        }
        $this->assertEquals(count($records), $count);
        $this->assertEquals(
            'SELECT COUNT(*) FROM (SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_) AS e',
            $actualSqls[0]
        );
        $this->assertEquals(
            'SELECT e0_.a AS a0, e0_.b AS b1 FROM Entity e0_ LIMIT '
            . BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE . ' OFFSET 0',
            $actualSqls[1]
        );
    }
}
