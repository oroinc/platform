<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLocaleBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroLocalizationTable($schema);
        $this->createOroFallbackLocalizedValueTable($schema);
        $this->createOroLocalizationTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroLocalizationForeignKeys($schema);
        $this->addOroFallbackLocalizedValueForeignKeys($schema);
        $this->addOroLocalizationTitleForeignKeys($schema);
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
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('language_code', 'string', ['length' => 64]);
        $table->addColumn('formatting_code', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create oro_fallback_localization_val table
     *
     * @param Schema $schema
     */
    protected function createOroFallbackLocalizedValueTable(Schema $schema)
    {
        $table = $schema->createTable('oro_fallback_localization_val');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_fallback', []);
        $table->addIndex(['string'], 'idx_string', []);
    }

    /**
     * Create oro_localization_title table
     *
     * @param Schema $schema
     */
    protected function createOroLocalizationTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_localization_title');
        $table->addColumn('localization_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['localization_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
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
     * Add oro_fallback_localization_val foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroFallbackLocalizedValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_fallback_localization_val');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_localization_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroLocalizationTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_localization_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_locale_value'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
