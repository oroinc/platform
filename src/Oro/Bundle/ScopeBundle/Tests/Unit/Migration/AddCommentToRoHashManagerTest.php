<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRoHashManager;
use PHPUnit\Framework\TestCase;

class AddCommentToRoHashManagerTest extends TestCase
{
    public function testAddRowHashCommentNotTable(): void
    {
        $this->expectException(SchemaException::class);
        $schema = new Schema();

        $em = $this->createMock(EntityManagerInterface::class);
        $manager = new AddCommentToRoHashManager($em);

        $manager->addRowHashComment($schema);
    }

    public function testAddRowHashComment(): void
    {
        $relations = [
            'customer_id',
            'customergroup_id',
            'localization_id',
            'organization_id',
            'user_id',
            'webcatalog_id',
            'website_id'
        ];
        $foreignKeys = [];
        foreach ($relations as $relation) {
            $foreign = $this->createMock(ForeignKeyConstraint::class);
            $foreign->expects($this->once())
                ->method('getLocalColumns')
                ->willReturn([$relation]);

            $foreignKeys[] = $foreign;
        }

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('listTableForeignKeys')
            ->willReturn($foreignKeys);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);

        sort($relations);
        $relations = implode(',', $relations);

        $column = $this->createMock(Column::class);
        $column->expects($this->once())
            ->method('setComment')
            ->with($relations);

        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('getColumn')
            ->with('row_hash')
            ->willReturn($column);

        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())
            ->method('getTable')
            ->with('oro_scope')
            ->willReturn($table);

        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $manager = new AddCommentToRoHashManager($em);

        $manager->addRowHashComment($schema);
    }
}
