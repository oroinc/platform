<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;

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
                    ['driver' => 'pdo_mysql']
                )
            );

        $config = [
            'pdo_mysql' => 'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql'
        ];

        $this->indexManager = new FulltextIndexManager($this->connection, $config);
    }

    public function testCreateIndexes()
    {
        $this->connection
            ->expects($this->once())
            ->method('query');

        $this->indexManager->createIndexes();
    }
}
