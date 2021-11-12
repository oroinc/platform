<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;

class DbalSchemaTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $schemaManger = $this->createMock(AbstractSchemaManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManger);

        new DbalSchema($connection, 'table-name');
    }

    public function testShouldCreateTable()
    {
        $schemaManger = $this->createMock(AbstractSchemaManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManger);

        $schema = new DbalSchema($connection, 'table-name');

        $tables = $schema->getTables();

        $this->assertCount(1, $tables);
        $table = current($tables);
        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals('table-name', $table->getName());
    }
}
