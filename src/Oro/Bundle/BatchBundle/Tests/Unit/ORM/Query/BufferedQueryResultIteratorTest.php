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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records))
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records)),
                    $this->createFetchStatementMock([$records[0], $records[1], $records[2]])
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records)),
                    $this->createFetchStatementMock([$records[0], $records[1]]),
                    $this->createFetchStatementMock([$records[2]])
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records)),
                    $this->createFetchStatementMock([$records[0], $records[1], $records[2]]),
                    $this->createFetchStatementMock([$records[3]])
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records)),
                    $this->createFetchStatementMock([$records[0], $records[1]]),
                    $this->createFetchStatementMock([$records[2]])
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

        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('query')
            ->will(
                $this->onConsecutiveCalls(
                    $this->createCountStatementMock(count($records)),
                    $this->createFetchStatementMock([$records[0], $records[1]]),
                    $this->createFetchStatementMock([$records[2]])
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
}
