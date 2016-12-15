<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

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

        $queries->addPostQuery(
            $this->getDropEntityConfigFieldQuery('Oro\Bundle\NavigationBundle\Entity\MenuUpdate', 'ownershipType')
        );
        $queries->addPostQuery(
            $this->getDropEntityConfigFieldQuery('Oro\Bundle\NavigationBundle\Entity\MenuUpdate', 'ownerId')
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return ParametrizedSqlMigrationQuery
     */
    protected function getDropEntityConfigFieldQuery($className, $fieldName)
    {
        $dropFieldIndexSql = 'DELETE FROM oro_entity_config_index_value'
            . ' WHERE entity_id IS NULL AND field_id IN ('
            . ' SELECT oecf.id FROM oro_entity_config_field AS oecf'
            . ' WHERE oecf.field_name = :field'
            . ' AND oecf.entity_id IN ('
            . ' SELECT oec.id'
            . ' FROM oro_entity_config AS oec'
            . ' WHERE oec.class_name = :class'
            . ' ))';
        $dropFieldSql      = 'DELETE FROM oro_entity_config_field'
            . ' WHERE field_name = :field'
            . ' AND entity_id IN ('
            . ' SELECT id'
            . ' FROM oro_entity_config'
            . ' WHERE class_name = :class'
            . ' )';

        $query = new ParametrizedSqlMigrationQuery();
        $query->addSql(
            $dropFieldIndexSql,
            ['field' => $fieldName, 'class' => $className],
            ['field' => 'string', 'class' => 'string']
        );
        $query->addSql(
            $dropFieldSql,
            ['field' => $fieldName, 'class' => $className],
            ['field' => 'string', 'class' => 'string']
        );

        return $query;
    }
}
