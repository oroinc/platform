<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;

class DbalSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $schemaManger = $this->createSchemaManagerMock();

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManger))
        ;

        new DbalSchema($connection, 'table-name');
    }

    public function testShouldCreateTable()
    {
        $schemaManger = $this->createSchemaManagerMock();

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManger))
        ;

        $schema = new DbalSchema($connection, 'table-name');

        $tables = $schema->getTables();

        $this->assertCount(1, $tables);
        $table = current($tables);

        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals('table-name', $table->getName());
    }

    private function createSchemaManagerMock()
    {
        return $this->getMock(AbstractSchemaManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->getMock(Connection::class, [], [], '', false);
    }
}
