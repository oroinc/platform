<?php

namespace Oro\Bundle\ThemeBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroThemeBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroThemeConfigurationTable($schema);
        $this->addOroThemeConfigurationForeignKeys($schema);
    }

    /**
     * Create oro_theme_configuration table
     */
    private function createOroThemeConfigurationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_theme_configuration');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn(
            'type',
            'string',
            ['default' => 'Storefront', 'length' => 255]
        );
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('theme', 'string', ['length' => 255]);
        $table->addColumn(
            'configuration',
            'array',
            ['notnull' => false, 'comment' => '(DC2Type:array)']
        );
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['organization_id'], 'idx_3ca89d3632c8a3de', []);
        $table->addIndex(['business_unit_owner_id'], 'idx_3ca89d3659294170', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_theme_configuration foreign keys.
     */
    private function addOroThemeConfigurationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_theme_configuration');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
