<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class ParametrizedSqlMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
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
            ->method('executeStatement');

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

        $this->connection->expects($this->exactly(6))
            ->method('executeStatement')
            ->withConsecutive(
                ['INSERT INTO test_table (name) VALUES (\'name\')'],
                ['INSERT INTO test_table (name) VALUES (?1)', ['test']],
                ['INSERT INTO test_table (name) VALUES (?1)', ['test'], ['string']],
                ['INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test']],
                ['INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test'], ['name' => 'string']],
                ['UPDATE test_table SET values = ?1 WHERE id = ?2', [[1, 2, 3], 1], ['array', 'integer']]
            );

        $this->addSqls($query);
        $query->execute($logger);

        $this->assertEquals(
            $this->getExpectedLogs(),
            $logger->getMessages()
        );
    }

    private function addSqls(ParametrizedSqlMigrationQuery $query)
    {
        $query->addSql('INSERT INTO test_table (name) VALUES (\'name\')');
        $query->addSql('INSERT INTO test_table (name) VALUES (?1)', ['test']);
        $query->addSql('INSERT INTO test_table (name) VALUES (?1)', ['test'], ['string']);
        $query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test']);
        $query->addSql('INSERT INTO test_table (name) VALUES (:name)', ['name' => 'test'], ['name' => 'string']);
        $query->addSql('UPDATE test_table SET values = ?1 WHERE id = ?2', [[1, 2, 3], 1], ['array', 'integer']);
    }

    private function getExpectedLogs(): array
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
