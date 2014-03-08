<?php

namespace Oro\Bundle\ConfigBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroConfigBundle extends Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_config **/
        $table = $schema->createTable('oro_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity', 'record_id'], 'CONFIG_UQ_ENTITY');
        /** End of generate table oro_config **/

        /** Generate table oro_config_value **/
        $table = $schema->createTable('oro_config_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('config_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('section', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('text_value', 'text', ['notnull' => false]);
        $table->addColumn('object_value', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('array_value', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'section', 'config_id'], 'CONFIG_VALUE_UQ_ENTITY');
        $table->addIndex(['config_id'], 'IDX_DAF6DF5524DB0683', []);
        /** End of generate table oro_config_value **/

        /** Generate foreign keys for table oro_config_value **/
        $table = $schema->getTable('oro_config_value');
        $table->addForeignKeyConstraint($schema->getTable('oro_config'), ['config_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_config_value **/

        // @codingStandardsIgnoreEnd
    }
}
