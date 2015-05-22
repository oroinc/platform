<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroSearchBundleInstaller implements Installation, ContainerAwareInterface, DatabasePlatformAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroSearchIndexDecimalTable($schema);
        $this->createOroSearchIndexIntegerTable($schema);
        $this->createOroSearchQueryTable($schema);
        $this->createOroSearchIndexDatetimeTable($schema);
        $this->createOroSearchItemTable($schema);
        $this->createOroSearchIndexTextTable($schema);
        $this->createOroSearchUpdateTable($schema);

        /** Foreign keys generation **/
        $this->addOroSearchIndexDecimalForeignKeys($schema);
        $this->addOroSearchIndexIntegerForeignKeys($schema);
        $this->addOroSearchIndexDatetimeForeignKeys($schema);
        $this->addOroSearchIndexTextForeignKeys($schema);

        // add search fulltext index query (only for ORM search engine)
        if ($this->container->has('oro_search.fulltext_index_manager')) {
            $query = $this->container->get('oro_search.fulltext_index_manager')->getQuery();
            $queries->addQuery($query);
        }
    }

    /**
     * Create oro_search_index_decimal table
     *
     * @param Schema $schema
     */
    protected function createOroSearchIndexDecimalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_index_decimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'decimal', ['scale' => 2]);
        $table->addIndex(['item_id'], 'idx_e0b9bb33126f525e', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_search_index_integer table
     *
     * @param Schema $schema
     */
    protected function createOroSearchIndexIntegerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_index_integer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'integer', []);
        $table->addIndex(['item_id'], 'idx_e04ba3ab126f525e', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_search_query table
     *
     * @param Schema $schema
     */
    protected function createOroSearchQueryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_query');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 250]);
        $table->addColumn('query', 'text', []);
        $table->addColumn('result_count', 'integer', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_search_index_datetime table
     *
     * @param Schema $schema
     */
    protected function createOroSearchIndexDatetimeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_index_datetime');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['item_id'], 'idx_459f212a126f525e', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_search_item table
     *
     * @param Schema $schema
     */
    protected function createOroSearchItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('record_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('changed', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['entity', 'record_id'], 'idx_entity');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity'], 'idx_entities', []);
        $table->addIndex(['alias'], 'idx_alias', []);
    }

    /**
     * Create oro_search_index_text table
     *
     * @param Schema $schema
     */
    protected function createOroSearchIndexTextTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_index_text');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 250]);
        $table->addColumn('value', 'text', []);
        $table->addIndex(['item_id'], 'idx_a0243539126f525e', []);
        $table->setPrimaryKey(['id']);

        if ($this->platform->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            $table->addOption('engine', PdoMysql::ENGINE_MYISAM);
        }
    }

    /**
     * Add oro_search_index_decimal foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSearchIndexDecimalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_decimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_search_index_integer foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSearchIndexIntegerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_integer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_search_index_datetime foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSearchIndexDatetimeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_datetime');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_search_index_text foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSearchIndexTextForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_search_index_text');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_search_item'),
            ['item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Create oro_search_update table
     *
     * @param Schema $schema
     */
    protected function createOroSearchUpdateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_search_update');
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->setPrimaryKey(['entity']);
    }
}
