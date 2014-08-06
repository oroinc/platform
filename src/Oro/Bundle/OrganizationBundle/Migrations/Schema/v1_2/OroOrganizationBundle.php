<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrganizationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOrganizationTable($schema);

        self::updateConfigs($schema, $queries);
    }

    /**
     * Modify table oro_organization
     *
     * @param Schema $schema
     */
    public static function updateOrganizationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_organization');

        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('enabled', 'boolean', []);

        $table->addUniqueIndex(['name'], 'UNIQ_BB42B65D5E237E06');
    }

    /**
     * Modify entity config to exclude currency and currency_precision fields
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public static function updateConfigs(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_organization');

        $table->dropColumn('currency');
        $table->dropColumn('currency_precision');

        if ($schema->hasTable('oro_entity_config_index_value') && $schema->hasTable('oro_entity_config_field')) {
            $queries->addPostQuery(
                'DELETE FROM oro_entity_config_index_value
                 WHERE entity_id IS NULL AND field_id IN(
                   SELECT oecf.id FROM oro_entity_config_field AS oecf
                   WHERE
                    (oecf.field_name = \'precision\' OR oecf.field_name = \'currency\')
                    AND
                    oecf.entity_id = (
                      SELECT oec.id
                      FROM oro_entity_config AS oec
                      WHERE oec.class_name = \'Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\Organization\'
                    )
                 );
                 DELETE FROM oro_entity_config_field
                   WHERE
                    field_name IN (\'precision\', \'currency\')
                    AND
                    entity_id IN (
                      SELECT id
                      FROM oro_entity_config
                      WHERE class_name = \'Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\Organization\'
                    )'
            );
        }
    }
}
