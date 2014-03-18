<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class ParametrizedSqlMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ParametrizedSqlMigrationQuery */
    protected $query;

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

        $this->query = new ParametrizedSqlMigrationQuery();
        $this->query->setConnection($this->connection);
    }

    public function testGetDescription()
    {
        $this->connection->expects($this->never())
            ->method('executeUpdate');

        $this->addSqls();

        $this->assertEquals(
            $this->getExpectedLogs(),
            $this->query->getDescription()
        );
    }

    public function testExecute()
    {
        $logger = new ArrayLogger();

        $this->connection->expects($this->at(0))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (\'name\')'
            );
        $this->connection->expects($this->at(1))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (?)',
                ['test']
            );
        // expects $this->connection->getDatabasePlatform at(2)
        $this->connection->expects($this->at(3))
            ->method('executeUpdate')
            ->with(
                'INSERT INTO test_table (name) VALUES (?)',
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
                'UPDATE test_table SET values = ? WHERE id = ?',
                [[1, 2, 3], 1],
                ['array', 'integer']
            );

        $this->addSqls();
        $this->query->execute($logger);

        $this->assertEquals(
            $this->getExpectedLogs(),
            $logger->getMessages()
        );

    }

    protected function addSqls()
    {
        $this->query->addSql('INSERT INTO test_table (name) VALUES (\'name\')');
        $this->query->addSql('INSERT INTO test_table (name) VALUES (?)', ['test']);
        $this->query->addSql('INSERT INTO test_table (name) VALUES (?)', ['test'], ['string']);
        $this->query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test']);
        $this->query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test'], ['name' => 'string']);
        $this->query->addSql('UPDATE test_table SET values = ? WHERE id = ?', [[1, 2, 3], 1], ['array', 'integer']);
    }

    protected function getExpectedLogs()
    {
        return [
            'INSERT INTO test_table (name) VALUES (\'name\')',
            'INSERT INTO test_table (name) VALUES (?)',
            'Parameters:',
            '[1] = test',
            'INSERT INTO test_table (name) VALUES (?)',
            'Parameters:',
            '[1] = test',
            'INSERT INTO test_table (name) VALUES (:name)',
            'Parameters:',
            '[name] = test',
            'INSERT INTO test_table (name) VALUES (:name)',
            'Parameters:',
            '[name] = test',
            'UPDATE test_table SET values = ? WHERE id = ?',
            'Parameters:',
            '[1] = a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}',
            '[2] = 1',
        ];
    }
}
