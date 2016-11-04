<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUserEmailIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // We should not do anything if index already exists.
        // This index could be added during update from old versions.
        $emailUserTable = $schema->getTable('oro_email_user');
        if ($emailUserTable->hasIndex('organization_id_received_at_idx')
        || !$emailUserTable->hasColumn('organization_id')
        || !$emailUserTable->hasColumn('received')) {
            return;
        }

        static::addIndexToEmailUserTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addIndexToEmailUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addIndex(['organization_id', 'received'], 'organization_id_received_at_idx');
    }
}
