<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migrations\Schema\v1_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1\UpdateAclEntriesMigrationQuery;

class UpdateAclEntriesMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    const ENTRIES_TABLE_NAME = 'acl_entries';
    const OBJECT_IDENTITIES_TABLE_NAME = 'acl_object_identities';
    const ACL_CLASSES_TABLE_NAME = 'acl_classes';

    /** @var \PHPUnit_Framework_MockObject_MockObject|Connection */
    protected $connection;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclManager */
    protected $aclManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclCacheInterface */
    protected $aclCache;

    /** @var UpdateAclEntriesMigrationQuery */
    protected $query;

    /** @var array */
    protected $keys = ['id', 'class_id', 'object_identity_id', 'field_name', 'ace_order', 'security_identity_id',
        'mask', 'granting', 'granting_strategy', 'audit_success', 'audit_failure'];

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->connection->expects($this->any())
            ->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return is_string($value) ? "'" . $value . "'" : $value;
                }
            );

        $this->aclManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclCache = $this->getMock('Symfony\Component\Security\Acl\Model\AclCacheInterface');

        $this->query = new UpdateAclEntriesMigrationQuery(
            $this->aclManager,
            $this->aclCache,
            self::ENTRIES_TABLE_NAME,
            self::OBJECT_IDENTITIES_TABLE_NAME,
            self::ACL_CLASSES_TABLE_NAME
        );
        $this->query->setConnection($this->connection);
    }

    protected function tearDown()
    {
        unset($this->query, $this->connection, $this->aclManager, $this->aclCache);
    }

    public function testGetDescription()
    {
        $this->assertConnectionCalled(true);
        $this->assertEntityAclExtensionCalled();
        $this->assertAclCacheCleared(true);

        $this->assertContains(
            'Update all ACE`s mask to support EntityMaskBuilder with dynamical identities',
            $this->query->getDescription()
        );
    }

    public function testExecute()
    {
        $this->assertConnectionCalled();
        $this->assertEntityAclExtensionCalled();
        $this->assertAclCacheCleared();

        $this->query->execute(new ArrayLogger());
    }

    /**
     * @return array
     */
    protected function getAces()
    {
        return array_map(
            function (array $values) {
                return array_combine($this->keys, $values);
            },
            $this->getAcesData()
        );
    }

    protected function assertEntityAclExtensionCalled()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityAclExtension $extension */
        $extension = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $extension->expects($this->exactly(count($this->getAcesData())))
            ->method('getAllMaskBuilders')
            ->willReturn($this->getMaskBuilders());

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector $extensionSelector */
        $extensionSelector = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector')
            ->disableOriginalConstructor()
            ->getMock();
        $extensionSelector->expects($this->once())
            ->method('select')
            ->with('entity:(root)')
            ->willReturn($extension);

        $this->aclManager->expects($this->once())
            ->method('getExtensionSelector')
            ->willReturn($extensionSelector);
    }

    /**
     * @param bool $noUpdates
     */
    protected function assertConnectionCalled($noUpdates = false)
    {
        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->with($this->isType('string'))
            ->willReturn($this->getAces());

        $updatesCount = $noUpdates ? 0 : count($this->getAcesData()) * count($this->getMaskBuilders());
        $data = $updatesCount ? $this->getExpectedExecuteUpdateParams() : [];

        $this->connection->expects($this->exactly($updatesCount))
            ->method('executeUpdate')
            ->willReturnCallback(
                function ($query, array $params = [], array $types = []) use (&$data) {
                    $index = array_search($params, $data, true);

                    $this->assertTrue($index !== false);
                    $this->assertContains(
                        (count($data[$index]) > 2 ? 'INSERT INTO ' : 'UPDATE ') . self::ENTRIES_TABLE_NAME,
                        $query
                    );

                    unset($data[$index]);
                }
            );
    }

    /**
     * @param bool $never
     */
    protected function assertAclCacheCleared($never = false)
    {
        $this->aclCache->expects($never ? $this->never() : $this->once())->method('clearCache');
    }

    /**
     * @return array|EntityMaskBuilder[]
     */
    protected function getMaskBuilders()
    {
        return [
            new EntityMaskBuilder(0 << 15, ['VIEW', 'CREATE', 'EDIT']),
            new EntityMaskBuilder(1 << 15, ['DELETE', 'ASSIGN', 'SHARE']),
            new EntityMaskBuilder(2 << 15, ['TEST']),
        ];
    }

    /**
     * @return array
     */
    protected function getAcesData()
    {
        return [
            [1, 1,    1, null, 0, 1, (1 << 30) - 1, true, 'all', false, false],
            [2, 2,    1, null, 0, 1, (1 << 15) - 1, true, 'all', false, false],
            [3, 1, null, null, 0, 1, (1 << 30) - 1, true, 'all', false, false],
            [4, 1,    2, null, 0, 1, (1 << 30) - 1, true, 'all', false, false],
            [5, 1,    1, null, 1, 2, (1 << 30) - 1, true, 'all', false, false]
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedExecuteUpdateParams()
    {
        $keys = $this->keys;
        array_shift($keys);

        $data = array_map(
            function (array $values) use ($keys) {
                return array_combine($keys, $values);
            },
            [
                [1,    1, null, 2, 1, 65535, true, 'all', false, false],
                [1,    1, null, 3, 1, 65536, true, 'all', false, false],
                [2,    1, null, 1, 1, 65535, true, 'all', false, false],
                [2,    1, null, 2, 1, 65536, true, 'all', false, false],
                [1, null, null, 1, 1, 65535, true, 'all', false, false],
                [1, null, null, 2, 1, 65536, true, 'all', false, false],
                [1,    2, null, 1, 1, 65535, true, 'all', false, false],
                [1,    2, null, 2, 1, 65536, true, 'all', false, false],
                [1,    1, null, 4, 2, 65535, true, 'all', false, false],
                [1,    1, null, 5, 2, 65536, true, 'all', false, false]
            ]
        );
        $data = array_merge(
            [
                ['mask' => 32767, 'id' => 1],
                ['mask' => 32767, 'id' => 2],
                ['mask' => 32767, 'id' => 3],
                ['mask' => 32767, 'id' => 4],
                ['mask' => 32767, 'id' => 5]
            ],
            $data
        );

        return $data;
    }
}
