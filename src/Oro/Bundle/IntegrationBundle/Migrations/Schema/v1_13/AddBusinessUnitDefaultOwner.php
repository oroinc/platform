<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddBusinessUnitDefaultOwner implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_channel');

        $table->addColumn('default_business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['default_business_unit_owner_id'], 'IDX_55B9B9C5FA248E2', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['default_business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
