<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateTranslationColumns implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroTranslationTable($schema);
        $this->addOroTranslationForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroTranslationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_translation');

        $table->changeColumn('translation_key_id', ['notnull' => true]);
        $table->changeColumn('language_id', ['notnull' => true]);

        $table->dropColumn('locale');
        $table->dropColumn('key');
        $table->dropColumn('domain');

        $table->addIndex(['language_id']);
        $table->addUniqueIndex(['language_id', 'translation_key_id'], 'language_key_uniq');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroTranslationForeignKeys(Schema $schema)
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
