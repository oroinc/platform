<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_7';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroLanguageTable($schema);
        $this->createOroTranslationTable($schema);
        $this->createOroTranslationKeyTable($schema);

        /** Foreign keys generation **/
        $this->addOroLanguageForeignKeys($schema);
        $this->addOroTranslationForeignKeys($schema);
    }

    /**
     * Create oro_language table
     */
    private function createOroLanguageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_language');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 16]);
        $table->addColumn('enabled', 'boolean', ['default' => false]);
        $table->addColumn('installed_build_date', 'datetime', ['notnull' => false]);
        $table->addColumn('local_files_language', 'boolean', ['default' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
    }

    /**
     * Create oro_translation table
     */
    private function createOroTranslationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('translation_key_id', 'integer');
        $table->addColumn('language_id', 'integer');
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('scope', 'smallint');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['language_id', 'translation_key_id'], 'language_key_uniq');
    }

    /**
     * Create oro_translation_key table
     */
    private function createOroTranslationKeyTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_translation_key');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 255]);
        $table->addColumn('domain', 'string', ['default' => 'messages', 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['domain', 'key'], 'oro_translation_key_uidx');
        /**
         * Required to support Case Sensitive keys in MySQL
         */
        $table->addOption('charset', 'utf8');
        $table->addOption('collate', 'utf8_bin');
    }

    /**
     * Add oro_language foreign keys.
     */
    private function addOroLanguageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_language');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_translation foreign keys.
     */
    private function addOroTranslationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_translation');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_translation_key'),
            ['translation_key_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_language'),
            ['language_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
