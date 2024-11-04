<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeEmailUserFolderRelation implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 1;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroEmailUserFoldersTable($schema);
        $this->updateOroEmailUserTable($schema);
    }

    /**
     * Add many to many relation table
     */
    private function createOroEmailUserFoldersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_user_folders');
        $table->addColumn('email_user_id', 'integer');
        $table->addColumn('folder_id', 'integer');
        $table->setPrimaryKey(['email_user_id', 'folder_id']);
        $table->addIndex(['email_user_id'], 'IDX_201746D71AAEBB5A');
        $table->addIndex(['folder_id'], 'IDX_201746D7162CB942');
        // temporary columns
        $table->addColumn('origin_id', 'integer');
        $table->addColumn('email_id', 'integer');
        $table->addIndex(['origin_id'], 'IDX_origin');
        $table->addIndex(['email_id'], 'IDX_email');

        $table = $schema->getTable('oro_email_user_folders');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_user'),
            ['email_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function updateOroEmailUserTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->removeForeignKey('fk_91f5cff6162cb942');
        $table->dropIndex('idx_91f5cff6162cb942');
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
    }
}
