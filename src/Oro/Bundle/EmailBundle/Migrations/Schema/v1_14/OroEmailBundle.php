<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_email_mailbox');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('origin_id', 'integer', ['nullable' => true]);
        $table->addColumn('smtp_settings', 'array', ['nullable' => false]);
        $table->addColumn('processor_id', 'integer');
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('oro_email_mailbox_processor');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);

        $table = $schema->getTable('oro_email_address');
        $table->addColumn('owner_mailbox_id', 'integer', ['notnull' => false]);
    }
}
