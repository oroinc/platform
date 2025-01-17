<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addColumn('default_user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['default_user_owner_id'], 'IDX_55B9B9C5A89019EA');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['default_user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_55B9B9C5A89019EA'
        );
    }
}
