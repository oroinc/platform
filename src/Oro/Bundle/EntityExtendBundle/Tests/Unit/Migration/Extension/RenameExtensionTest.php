<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendOptionsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $optionsManager;

    /**
     * @var AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databasePlatform;

    /**
     * @var RenameExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->optionsManager = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->databasePlatform = $this->getMockBuilder('Doctrine\DBAL\Platforms\MySqlPlatform')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->optionsManager->expects($this->at(0))
            ->method('getExtendOptions')
            ->willReturn([$oldTable . '!' . $column => $originalColumnOptions]);
        $this->optionsManager->expects($this->at(1))
            ->method('removeColumnOptions')
            ->with($oldTable, $column);
        $this->optionsManager->expects($this->at(2))
            ->method('setColumnOptions')
            ->with($newTable, $column, $originalColumnOptions);
        $this->optionsManager->expects($this->at(3))
            ->method('setColumnOptions')
            ->with($newTable, $column, $alteredColumnOptions);

        $schema = new Schema([new Table($oldTable)]);
        $queries = new QueryBag();
        $this->extension->renameTable($schema, $queries, $oldTable, $newTable);
        $this->assertNotEmpty($queries->getPostQueries()); // make sure that parent method was called
    }
}
