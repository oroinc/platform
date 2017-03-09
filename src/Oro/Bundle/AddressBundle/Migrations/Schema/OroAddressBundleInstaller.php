<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAddressBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroAddressTable($schema);
        $this->createOroAddressTypeTable($schema);
        $this->createOroAddressTypeTranslationTable($schema);
        $this->createOroDictionaryCountryTable($schema);
        $this->createOroDictionaryCountryTransTable($schema);
        $this->createOroDictionaryRegionTable($schema);
        $this->createOroDictionaryRegionTransTable($schema);

        /** Foreign keys generation **/
        $this->addOroAddressForeignKeys($schema);
        $this->addOroDictionaryRegionForeignKeys($schema);
    }

    /**
     * Create oro_address table
     *
     * @param Schema $schema
     */
    protected function createOroAddressTable(Schema $schema)
    {
        $table = $schema->createTable('oro_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['country_code'], 'IDX_C5E99957F026BB7C', []);
        $table->addIndex(['region_code'], 'IDX_C5E99957AEB327AF', []);
    }

    /**
     * Create oro_address_type table
     *
     * @param Schema $schema
     */
    protected function createOroAddressTypeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_address_type');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_8B3E52E3EA750E8');
    }

    /**
     * Create oro_address_type_translation table
     *
     * @param Schema $schema
     */
    protected function createOroAddressTypeTranslationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_address_type_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'address_type_translation_idx', []);
    }

    /**
     * Create oro_dictionary_country table
     *
     * @param Schema $schema
     */
    protected function createOroDictionaryCountryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dictionary_country');
        $table->addColumn('iso2_code', 'string', ['length' => 2]);
        $table->addColumn('iso3_code', 'string', ['length' => 3]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['iso2_code']);
        $table->addIndex(['name'], 'country_name_idx', []);
    }

    /**
     * Create oro_dictionary_country_trans table
     *
     * @param Schema $schema
     */
    protected function createOroDictionaryCountryTransTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dictionary_country_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 2]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'country_translation_idx', []);
    }

    /**
     * Create oro_dictionary_region table
     *
     * @param Schema $schema
     */
    protected function createOroDictionaryRegionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dictionary_region');
        $table->addColumn('combined_code', 'string', ['length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('code', 'string', ['length' => 32]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['combined_code']);
        $table->addIndex(['country_code'], 'IDX_8C71325AF026BB7C', []);
        $table->addIndex(['name'], 'region_name_idx', []);
    }

    /**
     * Create oro_dictionary_region_trans table
     *
     * @param Schema $schema
     */
    protected function createOroDictionaryRegionTransTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dictionary_region_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'region_translation_idx', []);
    }

    /**
     * Add oro_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dictionary_region foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDictionaryRegionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dictionary_region');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
