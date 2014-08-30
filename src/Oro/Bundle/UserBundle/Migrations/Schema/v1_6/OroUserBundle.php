<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOrganizationFields($schema);
        self::oroUserOrganizationTable($schema);
        self::oroUserOrganizationForeignKeys($schema);
        self::removeRoleOwner($schema, $queries);

        //Add organization fields to ownership entity config
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                'Oro\Bundle\UserBundle\Entity\Group',
                [
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            )
        );

        //Add organization fields to ownership entity config
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                'Oro\Bundle\UserBundle\Entity\User',
                [
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            )
        );
    }

    public static function removeRoleOwner(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_access_role');
        if ($table->hasColumn('business_unit_owner_id')) {
            $queries->addQuery(
                new UpdateRoleOwnerQuery()
            );

            if ($table->hasForeignKey('FK_673F65E759294170')) {
                $table->removeForeignKey('FK_673F65E759294170');
            }
            if ($table->hasIndex('IDX_F82840BC59294170')) {
                $table->dropIndex('IDX_F82840BC59294170');
            }

            $table->dropColumn('business_unit_owner_id');

            if ($schema->hasTable('oro_entity_config_index_value') && $schema->hasTable('oro_entity_config_field')) {
                $queries->addPostQuery(
                    'DELETE FROM oro_entity_config_index_value
                     WHERE entity_id IS NULL AND field_id IN(
                       SELECT oecf.id FROM oro_entity_config_field AS oecf
                       WHERE
                        (oecf.field_name = \'owner\')
                        AND
                        oecf.entity_id = (
                          SELECT oec.id
                          FROM oro_entity_config AS oec
                          WHERE oec.class_name = \'Oro\\\\Bundle\\\\UserBundle\\\\Entity\\\\Role\'
                        )
                     );
                     DELETE FROM oro_entity_config_field
                       WHERE
                        field_name IN (\'owner\')
                        AND
                        entity_id IN (
                          SELECT id
                          FROM oro_entity_config
                          WHERE class_name = \'Oro\\\\Bundle\\\\UserBundle\\\\Entity\\\\Role\'
                        );
                        '
                );
            }
        }
    }

    /**
     * Generate table oro_user_organization
     *
     * @param Schema $schema
     */
    public static function oroUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_user_organization');

        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['user_id', 'organization_id']);

        $table->addIndex(['user_id'], 'IDX_A9BB6519A76ED395', []);
        $table->addIndex(['organization_id'], 'IDX_A9BB651932C8A3DE', []);
    }

    /**
     * Generate foreign keys for table oro_user_organization
     *
     * @param Schema $schema
     */
    public static function oroUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_user_organization');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Adds organization_id field
     *
     * @param Schema $schema
     */
    public static function addOrganizationFields(Schema $schema)
    {
        $table = $schema->getTable('oro_access_group');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_FEF9EDB732C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_F82840BC32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
