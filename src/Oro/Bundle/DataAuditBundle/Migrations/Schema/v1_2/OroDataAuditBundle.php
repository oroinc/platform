<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataAuditBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOrganizationFields($schema);
    }

    /**
     * Adds organization_id field
     *
     * @param Schema $schema
     */
    public static function addOrganizationFields(Schema $schema)
    {
        $table = $schema->getTable('oro_audit');

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'idx_oro_audit_organization_id', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
