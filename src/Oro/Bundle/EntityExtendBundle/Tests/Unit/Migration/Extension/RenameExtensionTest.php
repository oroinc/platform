<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RenameExtensionTest extends TestCase
{
    private ExtendOptionsManager&MockObject $optionsManager;
    private AbstractPlatform&MockObject $databasePlatform;
    private RenameExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->optionsManager = $this->createMock(ExtendOptionsManager::class);
        $this->databasePlatform = $this->createMock(MySQLPlatform::class);

        $this->extension = new RenameExtension($this->optionsManager);
        $this->extension->setDatabasePlatform($this->databasePlatform);
    }

    public function testRenameTableFixOnlyTableName(): void
    {
        $oldTable = 'old_table';
        $newTable = 'new_table';
        $tableOptions = ['key' => 'value'];

        $this->optionsManager->expects($this->once())
            ->method('getExtendOptions')
            ->willReturn([$oldTable => $tableOptions]);
        $this->optionsManager->expects($this->once())
            ->method('removeTableOptions')
            ->with($oldTable);
        $this->optionsManager->expects($this->once())
            ->method('setTableOptions')
            ->with($newTable, $tableOptions);

        $schema = new Schema([new Table($oldTable)]);
        $queries = new QueryBag();
        $this->extension->renameTable($schema, $queries, $oldTable, $newTable);
        $this->assertNotEmpty($queries->getPostQueries()); // make sure that parent method was called
    }

    public function testRenameTableFixTableNameWithFieldName(): void
    {
        $oldTable = 'old_table';
        $newTable = 'new_table';
        $column = 'column';
        $originalColumnOptions = ['key' => 'value', '_target' => ['table_name' => $oldTable]];
        $alteredColumnOptions = ['key' => 'value', '_target' => ['table_name' => $newTable]];

        $this->optionsManager->expects($this->once())
            ->method('getExtendOptions')
            ->willReturn([$oldTable . '!' . $column => $originalColumnOptions]);
        $this->optionsManager->expects($this->once())
            ->method('removeColumnOptions')
            ->with($oldTable, $column);
        $this->optionsManager->expects($this->exactly(2))
            ->method('setColumnOptions')
            ->withConsecutive(
                [$newTable, $column, $originalColumnOptions],
                [$newTable, $column, $alteredColumnOptions]
            );

        $schema = new Schema([new Table($oldTable)]);
        $queries = new QueryBag();
        $this->extension->renameTable($schema, $queries, $oldTable, $newTable);
        $this->assertNotEmpty($queries->getPostQueries()); // make sure that parent method was called
    }
}
