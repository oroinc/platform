<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Depends to the UserBundle
 *
 * Class ChangeEmailUserFolderRelation
 * @package Oro\Bundle\UserBundle\Migrations\Schema\v1_18
 */
class ChangeEmailUserFolderRelation implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroEmailUserFoldersTable($schema);
        self::addOroEmailUserFoldersForeignKeys($schema);
        self::updateOroEmailUserTable($schema);
    }

    /**
     * Add many to many relation table
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function createOroEmailUserFoldersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_user_folders');
        $table->addColumn('email_user_id', 'integer', []);
        $table->addColumn('folder_id', 'integer', []);
        $table->setPrimaryKey(['email_user_id', 'folder_id']);
        $table->addIndex(['email_user_id'], 'IDX_201746D71AAEBB5A', []);
        $table->addIndex(['folder_id'], 'IDX_201746D7162CB942', []);
        // temporary columns
        $table->addColumn('origin_id', 'integer', []);
        $table->addColumn('email_id', 'integer', []);
        $table->addIndex(['origin_id'], 'IDX_origin', []);
        $table->addIndex(['email_id'], 'IDX_email', []);
    }

    /**
     * Add foreign keys
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addOroEmailUserFoldersForeignKeys(Schema $schema)
    {
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

    /**
     * Add origin to EmailUser
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateOroEmailUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->removeForeignKey('fk_91f5cff6162cb942');
        $table->dropIndex('idx_91f5cff6162cb942');
        $table->addColumn('origin_id', 'integer', ['notnull' => false]);
    }
}
