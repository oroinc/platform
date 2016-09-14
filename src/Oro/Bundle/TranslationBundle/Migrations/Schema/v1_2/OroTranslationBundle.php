<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroLanguageTable($schema);
        $this->createOroTranslationKeyTable($schema);

        $this->updateOroTranslationTable($schema, $queries);

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
     * Create oro_translation_key table
     *
     * @param Schema $schema
     */
    protected function createOroTranslationKeyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_translation_key');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 255]);
        $table->addColumn('domain', 'string', ['default' => 'messages', 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['key', 'domain'], 'key_domain_uniq');
        $table->addOption('collate', 'utf8_bin');
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroTranslationTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_translation');
        $table->addColumn('key_id', 'integer', ['notnull' => false]);
        $table->addColumn('language_id', 'integer', ['notnull' => false]);

        $table->dropIndex('MESSAGES_IDX');
        $table->dropIndex('MESSAGE_IDX');

        $queries->addQuery(new MigrateTranslationDataQuery());
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
