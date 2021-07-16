<?php

namespace Oro\Bundle\DraftBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates table for DraftProject.
 */
class OroDraftBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroDraftProjectTable($schema);

        /** Foreign keys generation **/
        $this->addOroDraftProjectForeignKeys($schema);
    }

    private function createOroDraftProjectTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_draft_project');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('title', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['user_owner_id'], 'IDX_9B6914467E3C61F9', []);
        $table->addIndex(['organization_id'], 'IDX_9B69144632C8A3DE', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_draft_project foreign keys.
     */
    private function addOroDraftProjectForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_draft_project');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
