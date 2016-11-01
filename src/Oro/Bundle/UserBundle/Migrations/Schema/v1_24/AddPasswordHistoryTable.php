<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPasswordHistoryTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::createOroUserPasswordHistoryTable($schema);
        $queries->addPostQuery(new MigrateUserPasswords());
    }

    /**
     * Create oro_user_password_history table
     *
     * @param Schema $schema
     */
    public static function createOroUserPasswordHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_user_password_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->addColumn('salt', 'string', ['length' => 255]);
        $table->addColumn('password_hash', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
