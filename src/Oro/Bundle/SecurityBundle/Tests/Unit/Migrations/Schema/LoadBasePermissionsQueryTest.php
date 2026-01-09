<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migrations\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoadBasePermissionsQueryTest extends TestCase
{
    protected Connection&MockObject $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
    }

    public function testExecute(): void
    {
        $this->assertConnectionCalled(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN'], 4);

        $query = new LoadBasePermissionsQuery();
        $query->setConnection($this->connection);
        $query->execute(new ArrayLogger());
    }

    protected function assertConnectionCalled(array $permissions, int $countCalls): void
    {
        $permissions = array_map(
            function ($permission) {
                return [$permission, $permission, true, ['default'], null];
            },
            $permissions
        );

        $data = array_map(
            function (array $values) {
                return array_combine(['name', 'label', 'is_apply_to_all', 'group_names', 'description'], $values);
            },
            $permissions
        );

        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SELECT name FROM oro_security_permission')
            ->willReturn([['name' => 'ASSIGN']]);

        $this->connection->expects($this->exactly($countCalls))
            ->method('executeStatement')
            ->willReturnCallback(function ($query, array $params = [], array $types = []) use (&$data) {
                $index = array_search($params, $data, true);

                self::assertNotFalse($index);
                self::assertStringContainsString('INSERT INTO oro_security_permission', $query);

                unset($data[$index]);
            });
    }
}
