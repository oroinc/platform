<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;

use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Migration\UpdateAclEntriesMigrationQuery;

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
            $this->connection,
            $this->aclManager,
            $this->aclCache,
            self::ENTRIES_TABLE_NAME,
            self::OBJECT_IDENTITIES_TABLE_NAME,
            self::ACL_CLASSES_TABLE_NAME
        );
    }

    protected function tearDown()
    {
        unset($this->query, $this->connection, $this->aclManager, $this->aclCache);
    }

    public function testGetDescription()
    {
        $this->assertEquals(
            'Update all ACE`s mask to support EntityMaskBuilder with dynamical identities',
            $this->query->getDescription()
        );
    }

    public function testExecute()
    {
        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->with($this->isType('string'))
            ->willReturn($this->getAces());
        $this->connection->expects($this->exactly(count($this->getAcesData()) * count($this->getMaskBuilders())))
            ->method('executeUpdate')
            ->with($this->isType('string'))
            ->willReturn($this->getAces());

        $this->assertEntityAclExtensionCalled();
        $this->assertAclCacheCleared();

        $logger = new ArrayLogger();

        $this->query->execute($logger);

        $expectedMessages = [
            'UPDATE acl_entries SET mask = 32767 WHERE id = 1',
            'UPDATE acl_entries SET mask = 32767 WHERE id = 2',
            'UPDATE acl_entries SET mask = 32767 WHERE id = 3',
            'UPDATE acl_entries SET mask = 32767 WHERE id = 4',
            'UPDATE acl_entries SET mask = 32767 WHERE id = 5',

            'VALUES (1, 1, NULL, 2, 1, 65535, 1, \'all\', 0, 0)',
            'VALUES (1, 1, NULL, 3, 1, 65536, 1, \'all\', 0, 0)',

            'VALUES (2, 1, NULL, 1, 1, 65535, 1, \'all\', 0, 0)',
            'VALUES (2, 1, NULL, 2, 1, 65536, 1, \'all\', 0, 0)',

            'VALUES (1, NULL, NULL, 1, 1, 65535, 1, \'all\', 0, 0)',
            'VALUES (1, NULL, NULL, 2, 1, 65536, 1, \'all\', 0, 0)',

            'VALUES (1, 2, NULL, 1, 1, 65535, 1, \'all\', 0, 0)',
            'VALUES (1, 2, NULL, 2, 1, 65536, 1, \'all\', 0, 0)',

            'VALUES (1, 1, NULL, 4, 2, 65535, 1, \'all\', 0, 0)',
            'VALUES (1, 1, NULL, 5, 2, 65536, 1, \'all\', 0, 0)',
        ];

        $messages = $logger->getMessages();

        foreach ($expectedMessages as $expectedMessage) {
            $found = false;

            foreach ($messages as $message) {
                if (strpos($message, $expectedMessage) !== false) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found, sprintf('Could not find expected message: "%s"', $expectedMessage));
        }
    }

    /**
     * @return array
     */
    protected function getAces()
    {
        $keys = ['id', 'class_id', 'object_identity_id', 'field_name', 'ace_order', 'security_identity_id', 'mask',
            'granting', 'granting_strategy', 'audit_success', 'audit_failure', 'class_type'];

        return array_map(
            function (array $values) use ($keys) {
                return array_combine($keys, $values);
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

    protected function assertAclCacheCleared()
    {
        $this->aclCache->expects($this->once())->method('clearCache');
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
            [1, 1,    1, null, 0, 1, (1 << 30) - 1, true, 'all', false, false, '(root)'],
            [2, 2,    1, null, 0, 1, (1 << 15) - 1, true, 'all', false, false, 'TestEntity'],
            [3, 1, null, null, 0, 1, (1 << 30) - 1, true, 'all', false, false, '(root)'],
            [4, 1,    2, null, 0, 1, (1 << 30) - 1, true, 'all', false, false, '(root)'],
            [5, 1,    1, null, 1, 2, (1 << 30) - 1, true, 'all', false, false, '(root)']
        ];
    }
}
