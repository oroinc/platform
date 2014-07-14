<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

class OroIntegrationBundle implements Migration, OrderedMigrationInterface
{
    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::modifyChannelTable($schema);

        $queries->addPostQuery(new MigrateValuesQuery());
    }

    /**
     * Change oro_integration_channel table
     *
     * @param Schema $schema
     */
    public static function modifyChannelTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addColumn('enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('synchronization_settings', Type::TEXT, ['notnull' => true, 'comment' => '(DC2Type:object)']);
        $table->addColumn('mapping_settings', Type::TEXT, ['notnull' => true, 'comment' => '(DC2Type:object)']);

        $table->addIndex(['organization_id'], 'IDX_55B9B9C532C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_55B9B9C532C8A3DE'
        );
    }
}
