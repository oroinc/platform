<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\IndexLimitExtension;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class IndexLimitExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IndexLimitExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new IndexLimitExtension();
    }

    public function testNonMysqlPlatform()
    {
        $platform = new PostgreSqlPlatform();
        $this->extension->setDatabasePlatform($platform);
        $table     = $this->getTable();
        $queries   = new QueryBag();
        $columns   = ['key'];
        $indexName = 'index_idx';
        $this->extension->addLimitedIndex($queries, $table, $columns, $indexName);

        $this->assertNotEmpty($table->getIndexes());
        $this->assertArrayHasKey($indexName, $table->getIndexes());
        $this->assertInstanceOf('Doctrine\DBAL\Schema\Index', $table->getIndex($indexName));
    }

    public function testAddLimitedIndex()
    {
        $platform = new MySqlPlatform();
        $this->extension->setDatabasePlatform($platform);
        $table     = $this->getTable();
        $tableName = $table->getName();
        $queries   = new QueryBag();
        $columns   = ['key', 'key2'];
        $indexName = 'index_idx';

        $this->extension->addLimitedIndex($queries, $table, $columns, $indexName);
        /** @var MigrationQuery $query */
        $query = $queries->getPostQueries()[0];
        $this->assertNotEmpty($query);
        $this->assertEquals("ALTER TABLE `$tableName` ADD INDEX `$indexName` (key(255),key2);", $query);
    }

    /**
     * @return Table
     */
    protected function getTable()
    {
        return new Table('table', [
            new Column(
                'key',
                Type::getType('string'),
                [
                    'length' => 500
                ]
            ),
            new Column(
                'key2',
                Type::getType('string'),
                [
                    'length' => 100
                ]
            )
        ]);
    }
}
