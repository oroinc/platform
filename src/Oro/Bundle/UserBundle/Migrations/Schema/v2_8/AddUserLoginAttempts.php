<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUserLoginAttempts implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroUserLoginAttemptsTable($schema);
        $this->addOroUserLoginAttemptsForeignKeys($schema);
        $queries->addPostQuery(new MigrateUserLoginAttemptsQuery());
    }

    private function createOroUserLoginAttemptsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_user_login');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('attempt_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('success', 'boolean', ['notnull' => true]);
        $table->addColumn('source', 'integer', ['notnull' => true]);
        $table->addColumn('username', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('ip', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('user_agent', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('context', 'json', ['notnull' => true, 'comment' => '(DC2Type:json)']);
        $table->addIndex(['user_id'], 'idx_aa4c6465a76ed395', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attempt_at'], 'oro_user_log_att_at_idx');
    }

    private function addOroUserLoginAttemptsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_user_login');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
