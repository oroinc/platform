<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroLanguageTable($schema);

        /** Foreign keys generation **/
        $this->addOroLanguageForeignKeys($schema);
    }

    /**
     * Create oro_language table
     *
     * @param Schema $schema
     */
    protected function createOroLanguageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_language');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 16]);
        $table->addColumn('enabled', 'boolean', ['default' => false]);
        $table->addColumn('installed_build_date', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
    }

    /**
     * Add oro_language foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroLanguageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_language');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
