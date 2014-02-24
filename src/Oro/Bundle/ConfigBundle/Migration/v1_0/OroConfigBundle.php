<?php

namespace Oro\Bundle\ConfigBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroConfigBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_config **/
        $table = $schema->createTable('oro_config');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('entity', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('record_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity', 'record_id'], 'CONFIG_UQ_ENTITY');
        /** End of generate table oro_config **/

        /** Generate table oro_config_value **/
        $table = $schema->createTable('oro_config_value');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('config_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('section', 'string', ['default' => null, 'notnull' => false, 'length' => 50, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('text_value', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('object_value', 'object', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('array_value', 'array', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => true, 'length' => 20, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'section', 'config_id'], 'CONFIG_VALUE_UQ_ENTITY');
        $table->addIndex(['config_id'], 'IDX_DAF6DF5524DB0683', []);
        /** End of generate table oro_config_value **/

        /** Generate foreign keys for table oro_config_value **/
        $table = $schema->getTable('oro_config_value');
        $table->addForeignKeyConstraint($schema->getTable('oro_config'), ['config_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_config_value **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
