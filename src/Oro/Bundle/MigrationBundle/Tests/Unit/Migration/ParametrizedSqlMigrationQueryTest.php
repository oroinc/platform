<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class ParametrizedSqlMigrationQueryTest extends \PHPUnit_Framework_TestCase
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

    public function testConstructor()
    {
        $query = new ParametrizedSqlMigrationQuery(
            'INSERT INTO test_table (name) VALUES (:name)',
            ['name' => 'test'],
            ['name' => 'string']
        );
        $query->setConnection($this->connection);

        $this->assertEquals(
            [
                'INSERT INTO test_table (name) VALUES (:name)',
                'Parameters:',
                '[name] = test',
            ],
            $query->getDescription()
        );
    }

    public function testGetDescription()
    {
        $query = new ParametrizedSqlMigrationQuery();
        $query->setConnection($this->connection);

        $this->connection->expects($this->never())
            ->method('executeUpdate');

        $this->addSqls($query);

        $this->assertEquals(
            $this->getExpectedLogs(),
            $query->getDescription()
        );
    }

    public function testExecute()
    {
        $query = new ParametrizedSqlMigrationQuery();
        $query->setConnection($this->connection);

        $logger = new ArrayLogger();

        $this->connection->expects($this->at(0))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (\'name\')'
            );
        $this->connection->expects($this->at(1))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (?1)',
                ['test']
            );
        // expects $this->connection->getDatabasePlatform at(2)
        $this->connection->expects($this->at(3))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (?1)',
                ['test'],
                ['string']
            );
        $this->connection->expects($this->at(4))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (:name)',
                ['name' => 'test']
            );
        // expects $this->connection->getDatabasePlatform at(5)
        $this->connection->expects($this->at(6))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (:name)',
                ['name' => 'test'],
                ['name' => 'string']
            );
        // expects $this->connection->getDatabasePlatform at(7)
        // expects $this->connection->getDatabasePlatform at(8)
        $this->connection->expects($this->at(9))
            ->method('executeUpdate')
            ->with(
                'UPDATE test_table SET values = ?1 WHERE id = ?2',
                [[1, 2, 3], 1],
                ['array', 'integer']
            );

        $this->addSqls($query);
        $query->execute($logger);

        $this->assertEquals(
            $this->getExpectedLogs(),
            $logger->getMessages()
        );
    }

    protected function addSqls(ParametrizedSqlMigrationQuery $query)
    {
        $query->addSql('INSERT INTO test_table (name) VALUES (\'name\')');
        $query->addSql('INSERT INTO test_table (name) VALUES (?1)', ['test']);
        $query->addSql('INSERT INTO test_table (name) VALUES (?1)', ['test'], ['string']);
        $query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test']);
        $query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test'], ['name' => 'string']);
        $query->addSql('UPDATE test_table SET values = ?1 WHERE id = ?2', [[1, 2, 3], 1], ['array', 'integer']);
    }

    protected function getExpectedLogs()
    {
        return [
            'INSERT INTO test_table (name) VALUES (\'name\')',
            'INSERT INTO test_table (name) VALUES (?1)',
            'Parameters:',
            '[1] = test',
            'INSERT INTO test_table (name) VALUES (?1)',
            'Parameters:',
            '[1] = test',
            'INSERT INTO test_table (name) VALUES (:name)',
            'Parameters:',
            '[name] = test',
            'INSERT INTO test_table (name) VALUES (:name)',
            'Parameters:',
            '[name] = test',
            'UPDATE test_table SET values = ?1 WHERE id = ?2',
            'Parameters:',
            '[1] = a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}',
            '[2] = 1',
        ];
    }
}
