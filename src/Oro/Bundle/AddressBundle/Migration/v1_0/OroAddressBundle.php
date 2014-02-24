<?php

namespace Oro\Bundle\AddressBundle\Migration\v1_0;

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
        // @codingStandardsIgnoreStart

        /** Generate table oro_address **/
        $table = $schema->createTable('oro_address');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('region_code', 'string', ['default' => null, 'notnull' => false, 'length' => 16, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('country_code', 'string', ['default' => null, 'notnull' => false, 'length' => 2, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('street', 'string', ['default' => null, 'notnull' => true, 'length' => 500, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('street2', 'string', ['default' => null, 'notnull' => false, 'length' => 500, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('city', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('postal_code', 'string', ['default' => null, 'notnull' => true, 'length' => 20, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('organization', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('region_text', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name_prefix', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('first_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('middle_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('last_name', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name_suffix', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['country_code'], 'IDX_C5E99957F026BB7C', []);
        $table->addIndex(['region_code'], 'IDX_C5E99957AEB327AF', []);
        /** End of generate table oro_address **/

        /** Generate table oro_address_type **/
        $table = $schema->createTable('oro_address_type');
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 16, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_8B3E52E3EA750E8');
        /** End of generate table oro_address_type **/

        /** Generate table oro_address_type_translation **/
        $table = $schema->createTable('oro_address_type_translation');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('foreign_key', 'string', ['default' => null, 'notnull' => true, 'length' => 16, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('locale', 'string', ['default' => null, 'notnull' => true, 'length' => 8, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('object_class', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'address_type_translation_idx', []);
        /** End of generate table oro_address_type_translation **/

        /** Generate table oro_dictionary_country **/
        $table = $schema->createTable('oro_dictionary_country');
        $table->addColumn('iso2_code', 'string', ['default' => null, 'notnull' => true, 'length' => 2, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('iso3_code', 'string', ['default' => null, 'notnull' => true, 'length' => 3, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['iso2_code']);
        $table->addIndex(['name'], 'country_name_idx', []);
        /** End of generate table oro_dictionary_country **/

        /** Generate table oro_dictionary_country_translation **/
        $table = $schema->createTable('oro_dictionary_country_translation');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('foreign_key', 'string', ['default' => null, 'notnull' => true, 'length' => 2, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('locale', 'string', ['default' => null, 'notnull' => true, 'length' => 8, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('object_class', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'country_translation_idx', []);
        /** End of generate table oro_dictionary_country_translation **/

        /** Generate table oro_dictionary_region **/
        $table = $schema->createTable('oro_dictionary_region');
        $table->addColumn('combined_code', 'string', ['default' => null, 'notnull' => true, 'length' => 16, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('country_code', 'string', ['default' => null, 'notnull' => false, 'length' => 2, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('code', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['combined_code']);
        $table->addIndex(['country_code'], 'IDX_8C71325AF026BB7C', []);
        $table->addIndex(['name'], 'region_name_idx', []);
        /** End of generate table oro_dictionary_region **/

        /** Generate table oro_dictionary_region_translation **/
        $table = $schema->createTable('oro_dictionary_region_translation');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('foreign_key', 'string', ['default' => null, 'notnull' => true, 'length' => 16, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('content', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('locale', 'string', ['default' => null, 'notnull' => true, 'length' => 8, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('object_class', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'region_translation_idx', []);
        /** End of generate table oro_dictionary_region_translation **/

        /** Generate foreign keys for table oro_address **/
        $table = $schema->getTable('oro_address');
        $table->addForeignKeyConstraint($schema->getTable('oro_dictionary_region'), ['region_code'], ['combined_code'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_dictionary_country'), ['country_code'], ['iso2_code'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_address **/

        /** Generate foreign keys for table oro_dictionary_region **/
        $table = $schema->getTable('oro_dictionary_region');
        $table->addForeignKeyConstraint($schema->getTable('oro_dictionary_country'), ['country_code'], ['iso2_code'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_dictionary_region **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
