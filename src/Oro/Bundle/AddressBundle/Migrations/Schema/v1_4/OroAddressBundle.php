<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAddressBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createContinentTables($schema);
        $this->addForeignKeys($schema);
    }

    public function createContinentTables(Schema $schema)
    {
        $table = $schema->createTable('oro_dictionary_continent');
        $table->addColumn('code', 'string', ['length' => 2]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['code']);
        $table->addIndex(['name'], 'continent_name_idx', []);

        $table = $schema->createTable('oro_dictionary_continent_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 2]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'continent_translation_idx', []);

        $table = $schema->getTable('oro_dictionary_country');
        $table->addColumn('continent_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addIndex(['continent_code'], 'IDX_6128B64616C569B', []);
    }

    public function addForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dictionary_country');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_continent'),
            ['continent_code'],
            ['code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
