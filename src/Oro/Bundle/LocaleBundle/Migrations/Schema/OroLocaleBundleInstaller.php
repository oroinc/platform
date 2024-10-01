<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroLocaleBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_7';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroLocalizationTable($schema);
        $this->createOroFallbackLocalizedValueTable($schema);
        $this->createOroLocalizationTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroLocalizationForeignKeys($schema);
        $this->addOroFallbackLocalizedValueForeignKeys($schema);
        $this->addOroLocalizationTitleForeignKeys($schema);

        $this->addRelationsToScope($schema);

        // Due to the cyclic dependency of bundles, it is not possible to create this key elsewhere during installation
        if ($schema->hasTable('oro_email_template_localized')) {
            $table = $schema->getTable('oro_email_template_localized');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_localization'),
                ['localization_id'],
                ['id'],
                ['onDelete' => 'CASCADE']
            );
        }
    }

    /**
     * Create oro_localization table
     */
    private function createOroLocalizationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_localization');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('language_id', 'integer');
        $table->addColumn('formatting_code', 'string', ['length' => 16]);
        $table->addColumn('rtl_mode', 'boolean', ['default' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create oro_fallback_localization_val table
     */
    private function createOroFallbackLocalizedValueTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_fallback_localization_val');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_fallback');
        $table->addIndex(['string'], 'idx_string');
    }

    /**
     * Create oro_localization_title table
     */
    private function createOroLocalizationTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_localization_title');
        $table->addColumn('localization_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['localization_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_localization foreign keys.
     */
    private function addOroLocalizationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_localization');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_language'),
            ['language_id'],
            ['id'],
            ['onDelete' => 'RESTRICT', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_fallback_localization_val foreign keys.
     */
    private function addOroFallbackLocalizedValueForeignKeys(Schema $schema): void
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
     */
    private function addOroLocalizationTitleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_localization_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
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

    private function addRelationsToScope(Schema $schema): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_scope',
            'localization',
            'oro_localization',
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ]
        );
    }
}
