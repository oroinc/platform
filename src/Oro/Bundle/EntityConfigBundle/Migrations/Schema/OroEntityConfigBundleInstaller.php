<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_0\OroEntityConfigBundle;

class OroEntityConfigBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroEntityConfigBundle::oroEntityConfigTable($schema);
        $schema->getTable('oro_entity_config')
            ->addColumn('data', 'array', ['notnull' => false]);

        OroEntityConfigBundle::oroEntityConfigFieldTable($schema);
        $schema->getTable('oro_entity_config_field')
            ->addColumn('data', 'array', ['notnull' => false]);

        /** Generate table oro_entity_config_index_value **/
        $table = $schema->createTable('oro_entity_config_index_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('scope', 'string', ['length' => 255]);
        $table->addColumn('value', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id'], 'IDX_256E3E9B81257D5D');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config'),
            ['entity_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addIndex(['field_id'], 'IDX_256E3E9B443707B0');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_config_field'),
            ['field_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addIndex(
            ['scope', 'code', 'value', 'entity_id'],
            'idx_entity_config_index_entity'
        );
        $table->addIndex(
            ['scope', 'code', 'value', 'field_id'],
            'idx_entity_config_index_field'
        );
        /** End of generate table oro_entity_config_index_value **/

        OroEntityConfigBundle::oroEntityConfigLogTable($schema);
        OroEntityConfigBundle::oroEntityConfigLogDiffTable($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetTable($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetRelationTable($schema, 'oro_entity_config_optset_rel');

        OroEntityConfigBundle::oroEntityConfigLogDiffForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetRelationForeignKeys($schema, 'oro_entity_config_optset_rel');
        OroEntityConfigBundle::oroEntityConfigFieldForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigLogForeignKeys($schema);
    }
}
