<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlMigrationQueryTest extends TestCase
{
    private Connection&MockObject $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
    }

    public function testConstructorWithString(): void
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

    public function testConstructorWithArray(): void
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

    public function testGetDescriptionWithoutSql(): void
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $this->assertEquals('', $query->getDescription());
    }

    public function testGetDescription(): void
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $this->connection->expects($this->never())
            ->method('executeStatement');

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

    public function testExecute(): void
    {
        $query = new SqlMigrationQuery();
        $query->setConnection($this->connection);

        $logger = new ArrayLogger();

        $this->connection->expects($this->exactly(3))
            ->method('executeStatement')
            ->withConsecutive(
                ['INSERT INTO test_table (name) VALUES (\'name\')'],
                ['INSERT INTO test_table (name) VALUES (\'name\')'],
                ['INSERT INTO test_table (test) VALUES (1)']
            );

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
