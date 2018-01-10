<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_32;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds the table that stores information about email origins that was failed to sync because of wrong credentials.
 */
class WrongCredentialsOriginTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::createCredentialsOriginTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function createCredentialsOriginTable(Schema $schema)
    {
        if (!$schema->hasTable('oro_email_wrong_creds_origin')) {
            $table = $schema->createTable('oro_email_wrong_creds_origin');
            $table->addColumn('origin_id', 'integer', ['notnull' => true]);
            $table->addColumn('owner_id', 'integer', ['notnull' => false]);
            $table->setPrimaryKey(['origin_id']);
            $table->addIndex(['owner_id']);
        }
    }
}
