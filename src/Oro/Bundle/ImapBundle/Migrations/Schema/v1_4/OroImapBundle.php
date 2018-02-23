<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addAccessTokenFieldsToOroEmailOriginTable($schema);
    }

    /**
     * Adds Access Token fields to the oro_email_origin table
     *
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    public static function addAccessTokenFieldsToOroEmailOriginTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('access_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('refresh_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('access_token_expires_at', 'datetime', ['notnull' => false]);
    }
}
