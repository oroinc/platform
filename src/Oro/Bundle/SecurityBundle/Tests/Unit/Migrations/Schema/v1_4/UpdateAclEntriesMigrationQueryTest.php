<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Migrations\Schema\v1_4;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Migrations\Schema\v1_4\UpdateAclEntriesMigrationQuery;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;

class UpdateAclEntriesMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_NUMBER_OF_PERMISSIONS = 13;

    private const ENTRIES_TABLE_NAME           = 'acl_entries';
    private const OBJECT_IDENTITIES_TABLE_NAME = 'acl_object_identities';
    private const ACL_CLASSES_TABLE_NAME       = 'acl_classes';

    private const UPDATE_QUERY = 'UPDATE acl_entries SET mask = :mask WHERE id = :id';
    private const DELETE_QUERY = 'DELETE FROM acl_entries WHERE id IN (:ids)';
    private const INSERT_QUERY = 'INSERT INTO acl_entries (class_id, object_identity_id, security_identity_id,'
    . ' ace_order, mask, granting, granting_strategy, audit_success, audit_failure)'
    . ' VALUES (:class_id, :object_identity_id, :security_identity_id,'
    . ' :ace_order, :mask, :granting, :granting_strategy, :audit_success, :audit_failure)';

    private const UPDATE_ORDER_QUERY = 'UPDATE acl_entries SET ace_order = :ace_order WHERE id = :id';

    /** @var \PHPUnit\Framework\MockObject\MockObject|Connection */
    private $connection;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclCacheInterface */
    private $aclCache;

    /** @var UpdateAclEntriesMigrationQuery */
    private $query;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());
        $this->connection->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function ($value) {
                return is_string($value) ? "'" . $value . "'" : $value;
            });

        $permissionConfigurationProvider = $this->createMock(PermissionConfigurationProvider::class);
        $permissionConfig = [];
        for ($i = 0; $i < self::MAX_NUMBER_OF_PERMISSIONS; $i++) {
            $permissionConfig['PERMISSION' . $i] = [];
        }
        $permissionConfigurationProvider->expects($this->any())
            ->method('getPermissionConfiguration')
            ->willReturn($permissionConfig);

        $this->aclCache = $this->createMock(AclCacheInterface::class);

        $this->query = new UpdateAclEntriesMigrationQuery(
            $permissionConfigurationProvider,
            $this->aclCache,
            self::ENTRIES_TABLE_NAME,
            self::OBJECT_IDENTITIES_TABLE_NAME,
            self::ACL_CLASSES_TABLE_NAME
        );
        $this->query->setConnection($this->connection);
    }

    private function getAceRow(int $id, int $sid, int $classId, ?int $oid, int $order, int $mask): array
    {
        return [
            'id'                   => (string)$id,
            'class_id'             => (string)$classId,
            'object_identity_id'   => null !== $oid ? (string)$oid : null,
            'security_identity_id' => (string)$sid,
            'ace_order'            => (string)$order,
            'mask'                 => (string)$mask,
            'granting'             => '1',
            'granting_strategy'    => 'all'
        ];
    }

    private function getUpdateOrderAceRow(int $id, int $sid, int $classId, ?int $oid, int $order): array
    {
        return [
            'id'                   => (string)$id,
            'class_id'             => (string)$classId,
            'object_identity_id'   => null !== $oid ? (string)$oid : null,
            'security_identity_id' => (string)$sid,
            'ace_order'            => (string)$order
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute()
    {
        $rootClassId = 1;
        $sid1 = 101;
        $sid2 = 102;
        $sid3 = 103;
        // order by: classId, oid, sid, mask
        // the max number of permissions is defined in MAX_NUMBER_OF_PERMISSIONS constant
        $aces = [
            // Permissions:                   4  8 16  0  0 16  0  0  0  1  0  2
            $this->getAceRow(1000, $sid1, $rootClassId, 1, 0, (1 << 2) + (1 << 8) + (1 << 14)),
            $this->getAceRow(1001, $sid1, $rootClassId, 1, 1, (1 << 14) + 32768),
            $this->getAceRow(1002, $sid1, $rootClassId, 1, 2, (1 << 0) + (1 << 11) + 32768 * 3),
            // Permissions:                  16 16 16 16 16 16 16 16 16 16 16 16 16 (16 16)
            $this->getAceRow(1005, $sid2, $rootClassId, 1, 3, (1 << 4) + (1 << 9) + (1 << 14)),
            $this->getAceRow(1006, $sid2, $rootClassId, 1, 4, (1 << 4) + (1 << 9) + (1 << 14) + 32768),
            $this->getAceRow(1007, $sid2, $rootClassId, 1, 5, (1 << 4) + (1 << 9) + (1 << 14) + 32768 * 2),
            $this->getAceRow(1008, $sid2, $rootClassId, 1, 6, (1 << 4) + (1 << 9) + (1 << 14) + 32768 * 3),
            $this->getAceRow(1009, $sid2, $rootClassId, 1, 6, (1 << 4) + (1 << 9) + (1 << 14) + 32768 * 4),
            // Permissions:                   0  0  0  0  0  0  0  0  0  0  0  0
            $this->getAceRow(1010, $sid3, $rootClassId, 1, 7, 0),
            $this->getAceRow(1011, $sid3, $rootClassId, 1, 8, 32768),
            $this->getAceRow(1012, $sid3, $rootClassId, 1, 9, 32768 * 2),
            $this->getAceRow(1013, $sid3, $rootClassId, 1, 10, 32768 * 3),
            // Own Permissions:              16  0  0  x  x  x 16  0  0  x  x  x
            // Merged With Root Permissions: 16  0  0  0  0 16 16  0  0  1  0  2
            $this->getAceRow(10, $sid1, 11, null, 0, 1 << 4),
            $this->getAceRow(11, $sid1, 11, null, 1, (1 << 4) + 32768 * 2),
            // Own Permissions:               x  x  x  0  0 16  x  x  x  x  x  x
            // Merged With Root Permissions: 16 16 16  0  0  1 16 16 16 16 16 16
            $this->getAceRow(12, $sid2, 11, null, 2, (1 << 10) + 32768),
            // Own Permissions:               x  x  x  x  x  x  x  x  x  0  0  1
            // Merged With Root Permissions: 16 16 16 16 16 16 16 16 16  0  0  1
            $this->getAceRow(13, $sid3, 11, null, 3, (1 << 0) + 32768 * 3),
            // Own Permissions:               x  x  x  x  x  x  x  x  x  1  0 16
            // Merged With Root Permissions:  4  8 16  0  0 16  0  0  0  1  0 16
            $this->getAceRow(20, $sid1, 12, null, 0, (1 << 0) + (1 << 14) + 32768 * 3),
            // Own Permissions:               x  x  x 16 16  0  x  x  x  x  x  x
            // Merged With Root Permissions: 16 16 16 16 16  0 16 16 16 16 16 16
            $this->getAceRow(21, $sid2, 12, null, 1, (1 << 4) + (1 << 9) + 32768),
            // Own Permissions:               x  x  x  0  0 16  1  0 16  x  x  x
            // Merged With Root Permissions:  4  8 16  0  0 16  1  0 16  1  0  2
            $this->getAceRow(30, $sid1, 13, null, 0, (1 << 14) + 32768),
            $this->getAceRow(31, $sid1, 13, null, 1, (1 << 0) + (1 << 14) + 32768 * 2)
        ];
        // order by: classId, oid, aceOrder
        $updateOrderAces = [
            $this->getUpdateOrderAceRow(1000, $sid1, $rootClassId, 1, 0),
            $this->getUpdateOrderAceRow(1001, $sid1, $rootClassId, 1, 1),
            $this->getUpdateOrderAceRow(1002, $sid1, $rootClassId, 1, 2),
            $this->getUpdateOrderAceRow(1005, $sid2, $rootClassId, 1, 3),
            $this->getUpdateOrderAceRow(1006, $sid2, $rootClassId, 1, 4),
            $this->getUpdateOrderAceRow(1007, $sid2, $rootClassId, 1, 5),
            $this->getUpdateOrderAceRow(1010, $sid3, $rootClassId, 1, 7),
            $this->getUpdateOrderAceRow(1011, $sid3, $rootClassId, 1, 8),
            $this->getUpdateOrderAceRow(1012, $sid3, $rootClassId, 1, 9),
            $this->getUpdateOrderAceRow(10, $sid1, 11, null, 0),
            $this->getUpdateOrderAceRow(11, $sid1, 11, null, 1),
            $this->getUpdateOrderAceRow(12, $sid2, 11, null, 2),
            $this->getUpdateOrderAceRow(13, $sid3, 11, null, 3),
            $this->getUpdateOrderAceRow(10001, $sid1, 11, null, 4),
            $this->getUpdateOrderAceRow(20, $sid1, 12, null, 0),
            $this->getUpdateOrderAceRow(21, $sid2, 12, null, 1),
            $this->getUpdateOrderAceRow(30, $sid1, 13, null, 0)
        ];

        $this->connection->expects($this->exactly(3))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(
                [['id' => $rootClassId]],
                $aces,
                $updateOrderAces
            );
        $this->connection->expects($this->exactly(21))
            ->method('executeStatement')
            ->withConsecutive(
                // mask: service bit index - 0; permissions - 4 8 16 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 16644, 'id' => 1000]],
                // mask: service bit index - 1; permissions - 16 0 0 0 1 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 34603024, 'id' => 1001]],
                // mask: service bit index - 2; permissions - 0 2 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 67108928, 'id' => 1002]],
                // mask: service bit index - 0; permissions - 16 16 16 16 16 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 17318416, 'id' => 1005]],
                // mask: service bit index - 1; permissions - 16 16 16 16 16 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 50872848, 'id' => 1006]],
                // mask: service bit index - 2; permissions - 16 16 16 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 67125776, 'id' => 1007]],
                // mask: service bit index - 0; permissions - 0 0 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 0, 'id' => 1010]],
                // mask: service bit index - 1; permissions - 0 0 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 33554432, 'id' => 1011]],
                // mask: service bit index - 2; permissions - 0 0 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 67108864, 'id' => 1012]],
                // mask: service bit index - 0; permissions - 16 0 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 16, 'id' => 10]],
                // mask: service bit index - 1; permissions - 16 16 0 0 1 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 34603536, 'id' => 11]],
                // mask: service bit index - 0; permissions - 16 16 16 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 16912, 'id' => 12]],
                // mask: service bit index - 1; permissions - 0 16 16 16 16 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 34603008, 'id' => 13]],
                // mask: service bit index - 2; permissions - 0 16 0 0 0 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 67109376, 'id' => 20]],
                // mask: service bit index - 1; permissions - 0 16 16 16 16 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 50872832, 'id' => 21]],
                // mask: service bit index - 1; permissions - 16 1 0 16 1 (in inverse order)
                [self::UPDATE_QUERY, ['mask' => 35127344, 'id' => 30]],
                [self::DELETE_QUERY, ['ids' => [1008, 1009, 1013, 31]]],
                [
                    self::INSERT_QUERY,
                    [
                        'class_id'             => 11,
                        'object_identity_id'   => null,
                        'security_identity_id' => $sid2,
                        'ace_order'            => 4,
                        // mask: service bit index - 1; permissions - 1 16 16 16 16 (in inverse order)
                        'mask'                 => 50872833,
                        'granting'             => true,
                        'granting_strategy'    => 'all',
                        'audit_success'        => false,
                        'audit_failure'        => false
                    ]
                ],
                [self::UPDATE_ORDER_QUERY, ['ace_order' => 6, 'id' => 1010]],
                [self::UPDATE_ORDER_QUERY, ['ace_order' => 7, 'id' => 1011]],
                [self::UPDATE_ORDER_QUERY, ['ace_order' => 8, 'id' => 1012]]
            );

        $this->aclCache->expects($this->once())
            ->method('clearCache');

        $this->query->execute(new ArrayLogger());
    }

    public function testExecuteWhenAceMasksWereAlreadyUpdated()
    {
        $rootClassId = 1;
        $sid1 = 101;
        $aces = [
            $this->getAceRow(1000, $sid1, $rootClassId, 1, 0, 0),
            $this->getAceRow(1001, $sid1, $rootClassId, 1, 1, 33554432),
            $this->getAceRow(10, $sid1, 11, null, 0, 1 << 4),
        ];

        $this->connection->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(
                [['id' => $rootClassId]],
                $aces
            );
        $this->connection->expects($this->never())
            ->method('executeStatement');

        $this->aclCache->expects($this->never())
            ->method('clearCache');

        $this->query->execute(new ArrayLogger());
    }
}
