<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\DBAL\DBALException;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexManagerTest extends \PHPUnit\Framework\TestCase
{
    const TABLE_NAME = 'oro_test_table';
    const INDEX_NAME = 'oro_test_table_value_idx';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $connection;

    /**
     * @var FulltextIndexManager
     */
    protected $indexManager;

    protected function setUp()
    {
        $this->connection = $this
            ->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $config = [
            DatabaseDriverInterface::DRIVER_MYSQL => 'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql'
        ];

        $this->indexManager = new FulltextIndexManager($this->connection, $config, self::TABLE_NAME, self::INDEX_NAME);
    }

    public function testCreateIndexes()
    {
        $this->connection
            ->expects($this->once())
            ->method('getParams')
            ->will(
                $this->returnValue(
                    ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
                )
            );

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with(PdoMysql::getPlainSql(self::TABLE_NAME, self::INDEX_NAME));

        $this->assertTrue($this->indexManager->createIndexes());
    }

    public function testCreateIndexWithError()
    {
        $this->connection
            ->expects($this->once())
            ->method('getParams')
            ->will(
                $this->returnValue(
                    ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
                )
            );

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->willThrowException(new DBALException());

        $this->assertFalse($this->indexManager->createIndexes());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Driver "pdo_pgsql" not found
     */
    public function testGetQueryForUnknownDriver()
    {
        $this->connection
            ->expects($this->once())
            ->method('getParams')
            ->will(
                $this->returnValue(
                    ['driver' => DatabaseDriverInterface::DRIVER_POSTGRESQL]
                )
            );

        $this->indexManager->getQuery();
    }
}
