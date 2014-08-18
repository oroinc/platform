<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

        $this->connection
            ->expects($this->once())
            ->method('getParams')
            ->will(
                $this->returnValue(
                    ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
                )
            );

        $config = [
            DatabaseDriverInterface::DRIVER_MYSQL => 'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql'
        ];

        $this->indexManager = new FulltextIndexManager($this->connection, $config);
    }

    public function testCreateIndexes()
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with(PdoMysql::getPlainSql());

        $this->indexManager->createIndexes();
    }
}
