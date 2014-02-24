<?php

namespace Oro\Bundle\SearchBundle\Migration\v1_0;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroSearchBundle implements Migration, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_search_index_datetime **/
        $table = $schema->createTable('oro_search_index_datetime');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('item_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('value', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'IDX_459F212A126F525E', []);
        /** End of generate table oro_search_index_datetime **/

        /** Generate table oro_search_index_decimal **/
        $table = $schema->createTable('oro_search_index_decimal');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('item_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('value', 'decimal', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 2, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'IDX_E0B9BB33126F525E', []);
        /** End of generate table oro_search_index_decimal **/

        /** Generate table oro_search_index_integer **/
        $table = $schema->createTable('oro_search_index_integer');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('item_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('value', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'IDX_E04BA3AB126F525E', []);
        /** End of generate table oro_search_index_integer **/

        /** Generate table oro_search_item **/
        $table = $schema->createTable('oro_search_item');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('entity', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('alias', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('record_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('title', 'string', ['default' => null, 'notnull' => false, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('changed', 'boolean', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updated_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['entity', 'record_id'], 'IDX_ENTITY');
        $table->addIndex(['alias'], 'IDX_ALIAS', []);
        $table->addIndex(['entity'], 'IDX_ENTITIES', []);
        /** End of generate table oro_search_item **/

        /** Generate table oro_search_query **/
        $table = $schema->createTable('oro_search_query');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('entity', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('query', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('result_count', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('created_at', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        /** End of generate table oro_search_query **/

        /** Generate foreign keys for table oro_search_index_datetime **/
        $table = $schema->getTable('oro_search_index_datetime');
        $table->addForeignKeyConstraint($schema->getTable('oro_search_item'), ['item_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_search_index_datetime **/

        /** Generate foreign keys for table oro_search_index_decimal **/
        $table = $schema->getTable('oro_search_index_decimal');
        $table->addForeignKeyConstraint($schema->getTable('oro_search_item'), ['item_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_search_index_decimal **/

        /** Generate foreign keys for table oro_search_index_integer **/
        $table = $schema->getTable('oro_search_index_integer');
        $table->addForeignKeyConstraint($schema->getTable('oro_search_item'), ['item_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_search_index_integer **/

        /** Generate table oro_search_index_text **/
        $table = $schema->createTable('oro_search_index_text');
        $table->addOption('engine' , 'MyISAM');
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('item_id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('field', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('value', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id'], 'IDX_A0243539126F525E', []);
        /** End of generate table oro_search_index_text **/

        // @codingStandardsIgnoreEnd

        // add search fulltext index query
        $queries = [];
        $connection = $this->container->get('doctrine')->getConnection();
        $config = $connection->getParams();
        $configClasses = $this->container->getParameter('oro_search.engine_orm');
        if (isset($configClasses[$config['driver']])) {
            $className = $configClasses[$config['driver']];
            $queries[] = $className::getPlainSql();
        }

        return $queries;
    }
}
