<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLocaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroLocalizationTable($schema);
        $this->createOroFallbackLocalizedValueTable($schema);

        /** Foreign keys generation **/
        $this->addOroLocalizationForeignKeys($schema);
        $this->addOroFallbackLocalizedValueForeignKeys($schema);
    }

    /**
     * Create oro_localization table
     *
     * @param Schema $schema
     */
    protected function createOroLocalizationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_localization');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 64]);
        $table->addColumn('language_code', 'string', ['length' => 64]);
        $table->addColumn('formatting_code', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create oro_fallback_locale_value table
     *
     * @param Schema $schema
     */
    protected function createOroFallbackLocalizedValueTable(Schema $schema)
    {
        $table = $schema->createTable('oro_fallback_locale_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback']);
        $table->addIndex(['string']);
    }

    /**
     * Add oro_localization foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroLocalizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_localization');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_fallback_locale_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroFallbackLocalizedValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_fallback_locale_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['locale_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
