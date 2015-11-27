<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQuery;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\ImportExportBundle\Writer\InsertFromSelectWriter;

class InsertFromSelectWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InsertFromSelectQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    /**
     * @var InsertFromSelectWriter
     */
    protected $writer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->query = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\InsertFromSelectQuery')
            ->disableOriginalConstructor()->getMock();

        $this->writer = new InsertFromSelectWriter($this->query);
    }

    public function testWrite()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $firstQueryBuilder */
        $firstQueryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        // Used for showing difference between first and second QueryBuilders
        /** @var QueryBuilder $secondQueryBuilder */
        $secondQueryBuilder = new QueryBuilder($em);

        $items = [
            [AbstractNativeQueryWriter::QUERY_BUILDER => $firstQueryBuilder],
            [AbstractNativeQueryWriter::QUERY_BUILDER => $secondQueryBuilder],
        ];

        $entityName = 'Bundle:EntityName';
        $fields = [
            'name',
            'description',
        ];

        $this->writer->setEntityName($entityName);
        $this->writer->setFields($fields);

        $this->query->expects($this->at(0))
            ->method('execute')
            ->with($entityName, $fields, $firstQueryBuilder);

        $this->query->expects($this->at(1))
            ->method('execute')
            ->with($entityName, $fields, $secondQueryBuilder);

        $this->writer->write($items);
    }
}
