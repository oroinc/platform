<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Migration\MysqlVersionCheckTrait;
use Oro\Bundle\SearchBundle\Migration\UseMyIsamEngineQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroSearchBundleInstaller implements Installation, ContainerAwareInterface, DatabasePlatformAwareInterface
{
    use ContainerAwareTrait;
    use DatabasePlatformAwareTrait;
    use MysqlVersionCheckTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_10';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroSearchIndexDecimalTable($schema);
        $this->createOroSearchIndexIntegerTable($schema);
        $this->createOroSearchQueryTable($schema);
        $this->createOroSearchIndexDatetimeTable($schema);
        $this->createOroSearchItemTable($schema);
        $this->createOroSearchIndexTextTable($schema, $queries);

        /** Foreign keys generation **/
        $this->addOroSearchIndexDecimalForeignKeys($schema);
        $this->addOroSearchIndexIntegerForeignKeys($schema);
        $this->addOroSearchIndexDatetimeForeignKeys($schema);
        $this->addOroSearchIndexTextForeignKeys($schema);
    }

    /**
     * Create oro_search_index_decimal table
     */
    private function createOroSearchIndexDecimalTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_search_index_decimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer');
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'decimal', ['precision' => 21, 'scale' => 6]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'idx_e0b9bb33126f525e');
        $table->addIndex(['field'], 'oro_search_index_decimal_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_search_index_decimal_item_field_idx');
    }

    /**
     * Create oro_search_index_integer table
     */
    private function createOroSearchIndexIntegerTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_search_index_integer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer');
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'bigint');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'idx_e04ba3ab126f525e');
        $table->addIndex(['field'], 'oro_search_index_integer_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_search_index_integer_item_field_idx');
    }

    /**
     * Create oro_search_query table
     */
    private function createOroSearchQueryTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_search_query');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 250]);
        $table->addColumn('query', 'text');
        $table->addColumn('result_count', 'integer');
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_search_index_datetime table
     */
    private function createOroSearchIndexDatetimeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_search_index_datetime');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer');
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'idx_459f212a126f525e');
        $table->addIndex(['field'], 'oro_search_index_datetime_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_search_index_datetime_item_field_idx');
    }

    /**
     * Create oro_search_item table
     */
    private function createOroSearchItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_search_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->addColumn('weight', 'decimal', ['precision' => 8, 'scale' => 4, 'default' => 1]);
        $table->addColumn('changed', 'boolean');
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity', 'record_id'], 'idx_entity');
        $table->addIndex(['entity'], 'idx_entities');
        $table->addIndex(['alias'], 'idx_alias');
    }

    /**
     * Create oro_search_index_text table
     */
    private function createOroSearchIndexTextTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_search_index_text');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer');
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'text');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'idx_a0243539126f525e');
        $table->addIndex(['field'], 'oro_search_index_text_field_idx');
        $table->addIndex(['item_id', 'field'], 'oro_search_index_text_item_field_idx');

        if ($this->isMysqlPlatform() && !$this->isInnoDBFulltextIndexSupported()) {
            $table->addOption('engine', PdoMysql::ENGINE_MYISAM);
            $queries->addPostQuery(new UseMyIsamEngineQuery('oro_search_index_text'));
        }

        $createFulltextIndexQuery = $this->container->get('oro_search.fulltext_index_manager')->getQuery();
        $queries->addPostQuery($createFulltextIndexQuery);
    }

    /**
     * Add oro_search_index_decimal foreign keys.
     */
    private function addOroSearchIndexDecimalForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_decimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_search_index_integer foreign keys.
     */
    private function addOroSearchIndexIntegerForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_integer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_search_index_datetime foreign keys.
     */
    private function addOroSearchIndexDatetimeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_datetime');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_search_index_text foreign keys.
     */
    private function addOroSearchIndexTextForeignKeys(Schema $schema): void
    {
        if (!$this->isMysqlPlatform() || $this->isInnoDBFulltextIndexSupported()) {
            $table = $schema->getTable('oro_search_index_text');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => null]
            );
        }
    }
}
