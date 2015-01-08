<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_property');
        $table->getColumn('background_color')->setOptions(['length' => 7]);
        $table->dropColumn('color');

        $table = $schema->getTable('oro_calendar_event');
        $table->addColumn('background_color', 'string', ['notnull' => false, 'length' => 7]);

        $queries->addPostQuery($this->getUpdateBackgroundColorValuesQuery());
        $queries->addPostQuery(
            $this->getDropEntityConfigFieldQuery('Oro\Bundle\CalendarBundle\Entity\CalendarProperty', 'color')
        );
    }

    /**
     * Gets a query to updates backgroundColor fields to full hex format (e.g. '#FFFFFF')
     *
     * @return ParametrizedSqlMigrationQuery
     */
    protected function getUpdateBackgroundColorValuesQuery()
    {
        return new ParametrizedSqlMigrationQuery(
            'UPDATE oro_calendar_property'
            . ' SET background_color = CONCAT(:prefix, background_color)'
            . ' WHERE background_color IS NOT NULL',
            ['prefix' => '#'],
            ['prefix' => 'string']
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
