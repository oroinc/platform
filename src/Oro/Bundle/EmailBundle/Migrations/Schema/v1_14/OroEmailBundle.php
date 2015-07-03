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
        $table->addColumn('imap_enabled', 'boolean', ['default' => false]);
        $table->addColumn('imap_origin_id', 'integer', ['nullable' => true]);
        $table->addColumn('smtp_enabled', 'boolean', ['default' => false]);
        $table->addColumn('smtp_host', 'string', ['length' => 255, 'nullable' => true]);
        $table->addColumn('smtp_port', 'integer', ['nullable' => true]);
        $table->addColumn('smtp_encryption', 'string', ['length' => 50, 'default' => 'none']);
        $table->addColumn('smtp_username', 'string', ['length' => 255, 'nullable' => true]);
        $table->addColumn('smtp_password', 'string', ['length' => 255, 'nullable' => true]);
        $table->addColumn('processor_id', 'integer');
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('oro_email_mailbox_processor');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->setPrimaryKey(['id']);
    }
}
