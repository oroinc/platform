<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Doctrine\ORM\Query;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\DriverMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\StatementMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity;

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

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records))
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);

        $this->assertEquals(count($records), $iterator->count());
    }

    public function testIteratorWithDefaultParameters()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records)),
                    $this->buildFetchStatement([$records[0], $records[1], $records[2]])
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
    }

    public function testIteratorWithMaxResultsSource()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records)),
                    $this->buildFetchStatement([$records[0], $records[1]]),
                    $this->buildFetchStatement([$records[2]])
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults(2);

        $iterator = new BufferedQueryResultIterator($source);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
    }

    public function testIteratorWithMaxResultsSourceAndExplicitlySetBufferSize()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
            ['a0' => '4'],
        ];

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records)),
                    $this->buildFetchStatement([$records[0], $records[1], $records[2]]),
                    $this->buildFetchStatement([$records[3]])
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o')
            ->setMaxResults(2);

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setBufferSize(3);

        $count = 0;
        foreach ($iterator as $record) {
            $this->assertInstanceOf('Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity', $record);
            $this->assertEquals($records[$count]['a0'], $record->a);
            $count++;
        }
        $this->assertEquals(count($records), $count);
    }

    public function testIteratorWithObjectHydrationMode()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records)),
                    $this->buildFetchStatement([$records[0], $records[1]]),
                    $this->buildFetchStatement([$records[2]])
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
    }

    public function testIteratorWithArrayHydrationMode()
    {
        $records = [
            ['a0' => '1'],
            ['a0' => '2'],
            ['a0' => '3'],
        ];

        $this->getConnection()->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->buildCountStatement(count($records)),
                    $this->buildFetchStatement([$records[0], $records[1]]),
                    $this->buildFetchStatement([$records[2]])
                )
            );

        $source = $this->em->createQueryBuilder()
            ->select('o')
            ->from('Stub:Entity', 'o');

        $iterator = new BufferedQueryResultIterator($source);
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);
        $iterator->setBufferSize(2);

        $this->assertEquals(count($records), $iterator->count());
        $count = 0;
        foreach ($iterator as $record) {
            $this->assertEquals($records[$count]['a0'], $record['a']);
            $count++;
        }
        $this->assertEquals(count($records), $count);
    }

    protected function getConnection()
    {
        $conn = $this->getMock('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\DriverConnectionMock');
        /** @var DriverMock $driver */
        $driver = $this->em->getConnection()->getDriver();
        $driver->setDriverConnection($conn);

        return $conn;
    }

    protected function buildCountStatement($count)
    {
        $countStatement = $this->getMock('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\StatementMock');
        $countStatement->expects($this->once())->method('fetchColumn')
            ->will($this->returnValue($count));

        return $countStatement;
    }

    protected function buildFetchStatement($records)
    {
        $statement = $this->getMock('Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\StatementMock');
        $statement->expects($this->exactly(count($records) + 1))->method('fetch')
            ->will(
                new \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls(
                    array_merge($records, [false])
                )
            );

        return $statement;
    }
}
