<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateLoginHistoryTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroUserLoginHistoryTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function createOroUserLoginHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_user_login_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('provider_class', 'string', ['length' => 255]);
        $table->addColumn('failed_attempts', 'integer', ['notnull' => false]);
        $table->addColumn('failed_daily_attempts', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addUniqueIndex(['user_id', 'provider_class']);
    }
}
