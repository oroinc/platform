<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddImpersonationColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addImpersonationColumn($schema);
    }

    /**
     * Adds impersonation_id column
     *
     * @param Schema $schema
     */
    public static function addImpersonationColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_audit');

        $table->addColumn('impersonation_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user_impersonation'),
            ['impersonation_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
