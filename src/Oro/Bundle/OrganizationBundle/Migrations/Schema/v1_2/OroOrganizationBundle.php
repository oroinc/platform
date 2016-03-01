<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;

class OroOrganizationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOrganizationTable($schema);
        self::updateConfigs($schema, $queries);

        //Add organization fields to ownership entity config
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                [
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            )
        );
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
        $table->addColumn(
            'created_at',
            'datetime',
            ['default' => null, 'comment' => '(DC2Type:datetime)', 'notnull' => false]
        );
        $table->addColumn(
            'updated_at',
            'datetime',
            ['default' => null, 'comment' => '(DC2Type:datetime)', 'notnull' => false]
        );
        $table->addColumn('enabled', 'boolean', ['default' => '1']);

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
                new ParametrizedSqlMigrationQuery(
                    'DELETE FROM oro_entity_config_index_value '
                    . 'WHERE entity_id IS NULL AND field_id IN ('
                    . 'SELECT oecf.id FROM oro_entity_config_field AS oecf '
                    . 'WHERE oecf.field_name IN (:field_names) '
                    . 'AND oecf.entity_id = ('
                    . 'SELECT oec.id FROM oro_entity_config AS oec WHERE oec.class_name = :class_name'
                    . '))',
                    [
                        'field_names' => ['precision', 'currency'],
                        'class_name'  => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
                    ],
                    [
                        'field_names' => Connection::PARAM_STR_ARRAY,
                        'class_name'  => Type::STRING
                    ]
                )
            );
            $queries->addPostQuery(
                new ParametrizedSqlMigrationQuery(
                    'DELETE FROM oro_entity_config_field '
                    . 'WHERE field_name IN (:field_names) '
                    . 'AND entity_id IN ('
                    . 'SELECT id FROM oro_entity_config WHERE class_name = :class_name'
                    . ')',
                    [
                        'field_names' => ['precision', 'currency'],
                        'class_name'  => 'Oro\Bundle\OrganizationBundle\Entity\Organization',
                    ],
                    [
                        'field_names' => Connection::PARAM_STR_ARRAY,
                        'class_name'  => Type::STRING
                    ]
                )
            );
        }
    }
}
