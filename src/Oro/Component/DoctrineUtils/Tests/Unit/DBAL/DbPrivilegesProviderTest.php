<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL;

use Oro\Component\DoctrineUtils\DBAL\DbPrivilegesProvider;

class DbPrivilegesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPostgresGrantedPrivileges()
    {
        $dbName = 'test';
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->exactly(4))
            ->method('exec')
            ->withConsecutive(
                ['CREATE TABLE oro_privileges_check(id INT)'],
                ['CREATE TEMP TABLE IF NOT EXISTS oro_privileges_check_tmp AS TABLE oro_privileges_check WITH NO DATA'],
                ['DROP TABLE oro_privileges_check'],
                ['DROP TABLE IF EXISTS oro_privileges_check_tmp']
            );

        $this->assertPgSqlPrivilegesFetch($dbName, $pdo);

        $privileges = DbPrivilegesProvider::getPostgresGrantedPrivileges($pdo, $dbName);

        $this->assertEqualsCanonicalizing(['CREATE', 'DROP', 'INSERT', 'SELECT', 'TEMPORARY', 'TRIGGER'], $privileges);
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
        $pdo->expects($this->exactly(3))
            ->method('exec')
            ->withConsecutive(
                ['CREATE TABLE oro_privileges_check(id INT)'],
                ['CREATE TEMP TABLE IF NOT EXISTS oro_privileges_check_tmp AS TABLE oro_privileges_check WITH NO DATA'],
                ['DROP TABLE oro_privileges_check']
            )
            ->willReturnCallback(function ($sql) {
                if ($sql === 'DROP TABLE oro_privileges_check') {
                    throw new \Exception();
                }
                return false;
            });

        $this->assertPgSqlPrivilegesFetch($dbName, $pdo);

        $privileges = DbPrivilegesProvider::getPostgresGrantedPrivileges($pdo, $dbName);

        $this->assertEqualsCanonicalizing(['CREATE', 'INSERT', 'SELECT', 'TEMPORARY', 'TRIGGER'], $privileges);
    }

    private function assertPgSqlPrivilegesFetch(
        string $dbName,
        \PDO|\PHPUnit\Framework\MockObject\MockObject $pdo
    ): void {
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
        $pdo = $this->createPDOMockWithMySQLGrants([
            'GRANT USAGE ON *.* TO `jeffrey`@`localhost`',
            'GRANT SELECT, INSERT, UPDATE, TRIGGER ON `db1`.* TO `jeffrey`@`localhost`',
            'GRANT SELECT, INSERT, TRIGGER ON `test`.* TO `jeffrey`@`localhost`',
            'GRANT CREATE TEMPORARY TABLES ON `test`.* TO `jeffrey`@`localhost`',
            "GRANT PROXY ON ''@'' TO 'root'@'localhost' WITH GRANT OPTION",
            'GRANT `r1`@`%`,`r2`@`%` TO `u1`@`localhost`',
            "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION"
        ]);

        $privileges = DbPrivilegesProvider::getMySqlGrantedPrivileges($pdo, $dbName);
        \sort($privileges);

        self::assertEquals(
            ['ALL PRIVILEGES', 'CREATE TEMPORARY TABLES', 'INSERT', 'SELECT', 'TRIGGER', 'USAGE'],
            $privileges
        );
    }

    public function testGetMySqlGrantedPrivilegesWithWildcards()
    {
        $dbName = 'test_something';

        // DB name with escaped _ and non-escaped _ and % wildcards
        $pdoEscapedNonEscaped = $this->createPDOMockWithMySQLGrants([
            'GRANT USAGE ON *.* TO `someuser`@`localhost`',
            'GRANT ALL PRIVILEGES ON `test\_s_me%`.* TO `someuser`@`localhost`'
        ]);

        $privileges = DbPrivilegesProvider::getMySqlGrantedPrivileges($pdoEscapedNonEscaped, $dbName);
        \sort($privileges);

        self::assertEquals(['ALL PRIVILEGES', 'USAGE'], $privileges);
    }

    private function createPDOMockWithMySQLGrants(array $grants = []): \PDO|\PHPUnit\Framework\MockObject\MockObject
    {
        $pdo = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects(self::once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN)
            ->willReturn($grants);

        $pdo->expects(self::once())
            ->method('query')
            ->with('SHOW GRANTS')
            ->willReturn($stmt);

        return $pdo;
    }
}
