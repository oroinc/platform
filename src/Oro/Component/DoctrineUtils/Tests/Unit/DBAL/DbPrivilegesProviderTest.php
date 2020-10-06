<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL;

use Oro\Component\DoctrineUtils\DBAL\DbPrivilegesProvider;

class DbPrivilegesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPostgresGrantedPrivileges()
    {
        $dbName = 'test';
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['CREATE TABLE oro_privileges_check(id INT)'],
                ['DROP TABLE oro_privileges_check']
            );

        $this->assertPgSqlPrivilegesFetch($dbName, $pdo);

        $privileges = DbPrivilegesProvider::getPostgresGrantedPrivileges($pdo, $dbName);
        sort($privileges);

        $this->assertEquals(['CREATE', 'DROP', 'INSERT', 'SELECT', 'TRIGGER'], $privileges);
    }

    public function testGetPostgresGrantedPrivilegesCreateTableException()
    {
        $dbName = 'test';
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('exec')
            ->with('CREATE TABLE oro_privileges_check(id INT)')
            ->willThrowException(new \Exception());

        $privileges = DbPrivilegesProvider::getPostgresGrantedPrivileges($pdo, $dbName);

        $this->assertEquals([], $privileges);
    }

    public function testGetPostgresGrantedPrivilegesDropTableException()
    {
        $dbName = 'test';
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['CREATE TABLE oro_privileges_check(id INT)'],
                ['DROP TABLE oro_privileges_check']
            )
            ->willReturnCallback(function ($sql) {
                if ($sql === 'DROP TABLE oro_privileges_check') {
                    throw new \Exception();
                }
            });

        $this->assertPgSqlPrivilegesFetch($dbName, $pdo);

        $privileges = DbPrivilegesProvider::getPostgresGrantedPrivileges($pdo, $dbName);
        sort($privileges);

        $this->assertEquals(['CREATE', 'INSERT', 'SELECT', 'TRIGGER'], $privileges);
    }

    /**
     * @param string $dbName
     * @param \PDO $pdo
     */
    private function assertPgSqlPrivilegesFetch(string $dbName, \PDO $pdo): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                ['tableSchema', $dbName, \PDO::PARAM_STR],
                ['tableName', 'oro_privileges_check', \PDO::PARAM_STR]
            );
        $stmt->expects($this->once())
            ->method('execute');
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN)
            ->willReturn(['INSERT', 'SELECT', 'TRIGGER']);

        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
    }

    public function testGetMySqlGrantedPrivileges()
    {
        $dbName = 'test';
        $pdo = $this->createMock(\PDO::class);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN)
            ->willReturn([
                'GRANT USAGE ON *.* TO `jeffrey`@`localhost`',
                'GRANT SELECT, INSERT, UPDATE, TRIGGER ON `db1`.* TO `jeffrey`@`localhost`',
                'GRANT SELECT, INSERT, TRIGGER ON `test`.* TO `jeffrey`@`localhost`',
                "GRANT PROXY ON ''@'' TO 'root'@'localhost' WITH GRANT OPTION",
                'GRANT `r1`@`%`,`r2`@`%` TO `u1`@`localhost`',
                "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION"
            ]);

        $pdo->expects($this->once())
            ->method('query')
            ->with('SHOW GRANTS')
            ->willReturn($stmt);

        $privileges = DbPrivilegesProvider::getMySqlGrantedPrivileges($pdo, $dbName);
        sort($privileges);

        $this->assertEquals(['ALL PRIVILEGES', 'INSERT', 'SELECT', 'TRIGGER', 'USAGE'], $privileges);
    }
}
