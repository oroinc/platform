<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\ImportExportBundle\Writer\InsertFromSelectWriter;

class InsertFromSelectWriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $queryExecutor;

    /**
     * @var InsertFromSelectWriter
     */
    protected $writer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->queryExecutor = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor')
            ->disableOriginalConstructor()->getMock();

        $this->writer = new InsertFromSelectWriter($this->queryExecutor);
    }

    public function testWrite()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $firstQueryBuilder */
        $firstQueryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
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

        $this->queryExecutor->expects($this->at(0))
            ->method('execute')
            ->with($entityName, $fields, $firstQueryBuilder);

        $this->queryExecutor->expects($this->at(1))
            ->method('execute')
            ->with($entityName, $fields, $secondQueryBuilder);

        $this->writer->write($items);
    }
}
