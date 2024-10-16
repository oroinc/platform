<?php

namespace Oro\Bundle\ConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroConfigBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroConfigTable($schema);
        $this->createOroConfigValueTable($schema);

        /** Foreign keys generation **/
        $this->addOroConfigValueForeignKeys($schema);
    }

    /**
     * Create oro_config table
     */
    private function createOroConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity', 'record_id'], 'CONFIG_UQ_ENTITY');
    }

    /**
     * Create oro_config_value table
     */
    private function createOroConfigValueTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_config_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('config_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('section', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('text_value', 'text', ['notnull' => false]);
        $table->addColumn('object_value', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('array_value', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'section', 'config_id'], 'CONFIG_VALUE_UQ_ENTITY');
        $table->addIndex(['config_id'], 'IDX_DAF6DF5524DB0683');
    }

    /**
     * Add oro_config_value foreign keys.
     */
    private function addOroConfigValueForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_config_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_config'),
            ['config_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
