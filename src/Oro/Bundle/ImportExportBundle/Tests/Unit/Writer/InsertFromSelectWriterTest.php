<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\ImportExportBundle\Writer\InsertFromSelectWriter;

class InsertFromSelectWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var InsertFromSelectQueryExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $queryExecutor;

    /** @var InsertFromSelectWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->queryExecutor = $this->createMock(InsertFromSelectQueryExecutor::class);

        $this->writer = new InsertFromSelectWriter($this->queryExecutor);
    }

    public function testWrite()
    {
        $firstQueryBuilder = $this->createMock(QueryBuilder::class);

        $em = $this->createMock(EntityManager::class);

        // Used for showing difference between first and second QueryBuilders
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

        $this->queryExecutor->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$entityName, $fields, $firstQueryBuilder],
                [$entityName, $fields, $secondQueryBuilder]
            );

        $this->writer->write($items);
    }
}
