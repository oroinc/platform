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

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->connection->expects($this->any())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
    }

    protected function tearDown()
    {
        unset($this->connection);
    }

    public function testExecute()
    {
        $this->assertConnectionCalled(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']);

        $query = new LoadBasePermissionsQuery();
        $query->setConnection($this->connection);
        $query->execute(new ArrayLogger());
    }

    /**
     * @param array $permissions
     */
    protected function assertConnectionCalled(array $permissions)
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

        $this->connection->expects($this->exactly(count($permissions)))
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
