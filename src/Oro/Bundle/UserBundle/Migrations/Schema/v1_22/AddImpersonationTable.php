<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddImpersonationTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::createOroUserImpersonationTable($schema);
    }

    /**
     * Create oro_user_impersonation table
     *
     * @param Schema $schema
     */
    public static function createOroUserImpersonationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_user_impersonation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('token', 'string', ['length' => 255]);
        $table->addColumn('expire_at', 'datetime', []);
        $table->addColumn('login_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['token'], 'token_idx', []);
        $table->addIndex(['user_id'], 'idx_385f2b6ca76ed395', []);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
