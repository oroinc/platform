<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroAddressBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        self::oroAddressTable($schema);
        self::oroAddressTypeTable($schema);
        self::oroAddressTypeTranslationTable($schema);
        self::oroDictionaryCountryTable($schema);
        self::oroDictionaryCountryTranslationTable($schema);
        self::oroDictionaryRegion($schema);
        self::oroDictionaryRegionTranslationTable($schema);
        self::addForeignKeys($schema);

        return [];
    }

    /**
     * Generate table oro_address
     *
     * @param Schema $schema
     */
    public static function oroAddressTable(Schema $schema)
    {
        /** Generate table oro_address **/
        $table = $schema->createTable('oro_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['length' => 255]);
        $table->addColumn('postal_code', 'string', ['length' => 20]);
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
        /** End of generate table oro_address **/
    }

    /**
     * Generate table oro_address_type
     *
     * @param Schema $schema
     */
    public static function oroAddressTypeTable(Schema $schema)
    {
        /** Generate table oro_address_type **/
        $table = $schema->createTable('oro_address_type');
        $table->addColumn('name', 'string', ['length' => 16]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_8B3E52E3EA750E8');
        /** End of generate table oro_address_type **/
    }

    /**
     * Generate table oro_address_type_translation
     *
     * @param Schema $schema
     */
    public static function oroAddressTypeTranslationTable(Schema $schema)
    {
        /** Generate table oro_address_type_translation **/
        $table = $schema->createTable('oro_address_type_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'address_type_translation_idx', []);
        /** End of generate table oro_address_type_translation **/
    }

    /**
     * Generate table oro_dictionary_country
     *
     * @param Schema $schema
     */
    public static function oroDictionaryCountryTable(Schema $schema)
    {
        /** Generate table oro_dictionary_country **/
        $table = $schema->createTable('oro_dictionary_country');
        $table->addColumn('iso2_code', 'string', ['length' => 2]);
        $table->addColumn('iso3_code', 'string', ['length' => 3]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['iso2_code']);
        $table->addIndex(['name'], 'country_name_idx', []);
        /** End of generate table oro_dictionary_country **/
    }

    public static function oroDictionaryCountryTranslationTable(
        Schema $schema,
        $tableName = 'oro_dictionary_country_translation'
    ) {
        /** Generate table oro_dictionary_country_translation **/
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 2]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'country_translation_idx', []);
        /** End of generate table oro_dictionary_country_translation **/
    }

    public static function oroDictionaryRegion(Schema $schema)
    {
        /** Generate table oro_dictionary_region **/
        $table = $schema->createTable('oro_dictionary_region');
        $table->addColumn('combined_code', 'string', ['length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('code', 'string', ['length' => 32]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['combined_code']);
        $table->addIndex(['country_code'], 'IDX_8C71325AF026BB7C', []);
        $table->addIndex(['name'], 'region_name_idx', []);
        /** End of generate table oro_dictionary_region **/
    }

    public static function oroDictionaryRegionTranslationTable(
        Schema $schema,
        $tableName = 'oro_dictionary_region_translation'
    ) {
        /** Generate table oro_dictionary_region_translation **/
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 16]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'region_translation_idx', []);
        /** End of generate table oro_dictionary_region_translation **/
    }

    public static function addForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_address **/
        $table = $schema->getTable('oro_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_address **/

        /** Generate foreign keys for table oro_dictionary_region **/
        $table = $schema->getTable('oro_dictionary_region');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_dictionary_region **/
    }
}
