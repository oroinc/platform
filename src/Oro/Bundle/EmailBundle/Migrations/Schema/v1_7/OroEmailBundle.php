<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createEmailThreadTable($schema);
        $this->updateEmailTable($schema);
    }

    private function createEmailThreadTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_thread');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('last_unseen_email_id', 'integer', ['notnull' => false]);
        $table->addColumn('created', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    private function updateEmailTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email');
        $table->addColumn('is_head', 'boolean', ['default' => true]);
        $table->addColumn('is_seen', 'boolean', ['default' => true]);
        $table->addColumn('thread_id', 'integer', ['notnull' => false]);
        $table->addColumn('refs', 'text', ['notnull' => false]);
        $table->addIndex(['is_head'], 'oro_email_is_head');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_thread'),
            ['thread_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table = $schema->getTable('oro_email_thread');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['last_unseen_email_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
