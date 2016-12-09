<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_30;

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
        self::createSmtpSettingsTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function createSmtpSettingsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_email_smtp_settings');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('port', 'integer', ['notnull' => false, 'length' => 10]);
        $table->addColumn('encryption', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('username', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('password', 'text', ['notnull' => false, 'length' => 16777216]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'IDX_574C364F32C8A3DE', []);
    }
}
