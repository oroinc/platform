<?php
/**
 * Created by PhpStorm.
 * User: de-key
 * Date: 6/16/14
 * Time: 7:17 PM
 */

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_channel');

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->addIndex(['organization_id'], 'organization_id', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_55B9B9C5A89019EA'
        );
    }
}
