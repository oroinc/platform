<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendOptionsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsManager;

    /** @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject */
    private $databasePlatform;

    /** @var RenameExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->optionsManager = $this->createMock(ExtendOptionsManager::class);
        $this->databasePlatform = $this->createMock(MySqlPlatform::class);

        $this->extension = new RenameExtension($this->optionsManager);
        $this->extension->setDatabasePlatform($this->databasePlatform);
    }

    public function testRenameTableFixOnlyTableName()
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

    public function testRenameTableFixTableNameWithFieldName()
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
