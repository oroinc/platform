<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migrations\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery;

class LoadBasePermissionsQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Connection */
    protected $connection;

    /** @var LoadBasePermissionsQuery */
    protected $query;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->connection->expects($this->any())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());

        $this->query = new LoadBasePermissionsQuery();
        $this->query->setConnection($this->connection);
    }

    protected function tearDown()
    {
        unset($this->query, $this->connection);
    }

    public function testExecute()
    {
        $this->assertConnectionCalled();

        $this->query->execute(new ArrayLogger());
    }

    protected function assertConnectionCalled()
    {
        $data = array_map(
            function (array $values) {
                return array_combine(['name', 'label', 'is_apply_to_all', 'group_names', 'description'], $values);
            },
            [
                ['VIEW', 'VIEW', true, ['default'], null],
                ['CREATE', 'CREATE', true, ['default'], null],
                ['EDIT', 'EDIT', true, ['default'], null],
                ['DELETE', 'DELETE', true, ['default'], null],
                ['ASSIGN', 'ASSIGN', true, ['default'], null],
                ['SHARE', 'SHARE', true, ['default'], null]
            ]
        );

        $this->connection->expects($this->exactly(6))
            ->method('executeUpdate')
            ->willReturnCallback(
                function ($query, array $params = [], array $types = []) use (&$data) {
                    $index = array_search($params, $data, true);

                    $this->assertTrue($index !== false);
                    $this->assertContains('INSERT INTO oro_security_permission', $query);

                    unset($data[$index]);
                }
            );
    }
}
