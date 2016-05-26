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
        $this->createOroLocaleSetTable($schema);

        /** Foreign keys generation **/
        $this->addOroLocaleSetForeignKeys($schema);
    }

    /**
     * Create oro_locale_set table
     *
     * @param Schema $schema
     */
    protected function createOroLocaleSetTable(Schema $schema)
    {
        $table = $schema->createTable('oro_locale_set');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 64]);
        $table->addColumn('i18n_code', 'string', ['length' => 64]);
        $table->addColumn('l10n_code', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Add oro_locale_set foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroLocaleSetForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_locale_set');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_locale_set'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
