<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class SqlMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));
    }

    public function testConstructorWithString()
    {
        $query = new SqlMigrationQuery(
            'INSERT INTO test_table (name) VALUES (:name)'
        );
        $query->setConnection($this->connection);

        $this->assertEquals(
            'INSERT INTO test_table (name) VALUES (:name)',
            $query->getDescription()
        );
    }

    public function testConstructorWithArray()
    {
        $query = new SqlMigrationQuery(
            [
                'INSERT INTO test_table (name) VALUES (:name)',
                'INSERT INTO test_table (test) VALUES (1)'
            ]
        );
        $query->setConnection($this->connection);

        $this->assertEquals(
            [
                'INSERT INTO test_table (name) VALUES (:name)',
                'INSERT INTO test_table (test) VALUES (1)'
            ],
            $query->getDescription()
        );
    }

    public function testGetDescriptionWithoutSql()
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $this->assertEquals('', $query->getDescription());
    }

    public function testGetDescription()
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $this->connection->expects($this->never())
            ->method('executeUpdate');

        $query->addSql('INSERT INTO test_table (name) VALUES (\'name\')');
        $this->assertEquals(
            'INSERT INTO test_table (name) VALUES (\'name\')',
            $query->getDescription()
        );

        $query->addSql('INSERT INTO test_table (test) VALUES (1)');
        $this->assertEquals(
            [
                'INSERT INTO test_table (name) VALUES (\'name\')',
                'INSERT INTO test_table (test) VALUES (1)',
            ],
            $query->getDescription()
        );
    }

    public function testExecute()
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $logger = new ArrayLogger();

        $this->connection->expects($this->at(0))
            ->method('executeUpdate')
            ->with('INSERT INTO test_table (name) VALUES (\'name\')');
        $this->connection->expects($this->at(1))
            ->method('executeUpdate')
            ->with('INSERT INTO test_table (name) VALUES (\'name\')');
        $this->connection->expects($this->at(2))
            ->method('executeUpdate')
            ->with('INSERT INTO test_table (test) VALUES (1)');

        $query->addSql('INSERT INTO test_table (name) VALUES (\'name\')');
        $query->execute($logger);
        $this->assertEquals(
            [
                'INSERT INTO test_table (name) VALUES (\'name\')'
            ],
            $logger->getMessages()
        );

        $query->addSql('INSERT INTO test_table (test) VALUES (1)');
        $query->execute($logger);
        $this->assertEquals(
            [
                'INSERT INTO test_table (name) VALUES (\'name\')',
                'INSERT INTO test_table (name) VALUES (\'name\')',
                'INSERT INTO test_table (test) VALUES (1)',
            ],
            $logger->getMessages()
        );
    }
}
