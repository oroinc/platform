<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropOwnershipFromMenuUpdateTable implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->dropColumn('ownership_type');
        $table->dropColumn('owner_id');
        if ($table->hasIndex('oro_navigation_menu_upd_uidx')) {
            $table->dropIndex('oro_navigation_menu_upd_uidx');
        }

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
                        'field_names' => ['ownership_type', 'owner_id'],
                        'class_name'  => 'Oro\Bundle\NavigationBundle\Entity\MenuUpdate',
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
                        'field_names' => ['ownership_type', 'owner_id'],
                        'class_name'  => 'Oro\Bundle\NavigationBundle\Entity\MenuUpdate',
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
