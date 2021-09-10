<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Statement;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MutableAclProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MutableAclProvider */
    private $provider;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var PermissionGrantingStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionGrantingStrategy;

    /** @var SecurityIdentityToStringConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sidConverter;

    /** @var AclCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->onlyMethods(['convertBooleans'])
            ->getMockForAbstractClass();
        $platform->expects($this->any())
            ->method('convertBooleans')
            ->willReturnMap([
                [false, '0'],
                [true, '1'],
            ]);
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);
        $this->connection->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function ($input) {
                return "'" . $input . "'";
            });

        $this->permissionGrantingStrategy = $this->createMock(PermissionGrantingStrategyInterface::class);
        $this->cache = $this->createMock(AclCache::class);
        $this->sidConverter = $this->createMock(SecurityIdentityToStringConverterInterface::class);

        $this->provider = new MutableAclProvider(
            $this->connection,
            $this->permissionGrantingStrategy,
            [
                'sid_table_name' => 'acl_security_identities',
                'oid_table_name' => 'acl_oid_table',
                'class_table_name' => 'acl_class_table',
                'oid_ancestors_table_name' => 'acl_oid_ancestors_table',
                'entry_table_name' => 'acl_entry_table'
            ],
            $this->cache
        );
        $this->provider->setSecurityIdentityToStringConverter($this->sidConverter);
    }

    public function testBeginTransaction()
    {
        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $this->provider->beginTransaction();
    }

    public function testCommit()
    {
        $this->connection->expects($this->once())
            ->method('commit');
        $this->provider->commit();
    }

    public function testRollBack()
    {
        $this->connection->expects($this->once())
            ->method('rollBack');
        $this->provider->rollBack();
    }

    /**
     * @dataProvider deleteSecurityIdentityProvider
     */
    public function testDeleteSecurityIdentity(SecurityIdentityInterface $sid, $parameters)
    {
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM acl_security_identities WHERE identifier = ? AND username = ?',
                $parameters,
                [ParameterType::STRING, ParameterType::BOOLEAN]
            );
        $this->provider->deleteSecurityIdentity($sid);
    }

    /**
     * @dataProvider updateSecurityIdentityProvider
     */
    public function testUpdateSecurityIdentity(SecurityIdentityInterface $sid, $oldName, $parameters)
    {
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE acl_security_identities SET identifier = ? WHERE identifier = ? AND username = ?',
                $parameters,
                [ParameterType::STRING, ParameterType::STRING, ParameterType::BOOLEAN]
            );
        $this->provider->updateSecurityIdentity($sid, $oldName);
    }

    /**
     * @dataProvider updateSecurityIdentityNoChangesProvider
     */
    public function testUpdateSecurityIdentityShouldThrowInvalidArgumentException(
        SecurityIdentityInterface $sid,
        $oldName
    ) {
        $this->expectException(\InvalidArgumentException::class);
        $this->provider->updateSecurityIdentity($sid, $oldName);
    }

    public static function deleteSecurityIdentityProvider(): array
    {
        return [
            [
                new UserSecurityIdentity('test', 'Acme\User'),
                ['Acme\User-test', true]
            ],
            [
                new RoleSecurityIdentity('ROLE_TEST'),
                ['ROLE_TEST', false]
            ]
        ];
    }

    public static function updateSecurityIdentityProvider(): array
    {
        return [
            [
                new UserSecurityIdentity('test', 'Acme\User'),
                'old',
                ['Acme\User-test', 'Acme\User-old', true]
            ],
            [
                new RoleSecurityIdentity('ROLE_TEST'),
                'ROLE_OLD',
                ['ROLE_TEST', 'ROLE_OLD', false]
            ]
        ];
    }

    public static function updateSecurityIdentityNoChangesProvider(): array
    {
        return [
            [new UserSecurityIdentity('test', 'Acme\User'), 'test'],
            [new RoleSecurityIdentity('ROLE_TEST'), 'ROLE_TEST'],
        ];
    }

    public function testDeleteAclClass()
    {
        $oid = new ObjectIdentity('entity', 'Test\Class');

        $provider = $this->getMockBuilder(MutableAclProvider::class)
            ->onlyMethods(['deleteAcl'])
            ->setConstructorArgs([
                $this->connection,
                $this->createMock(PermissionGrantingStrategyInterface::class),
                ['class_table_name' => 'acl_classes']
            ])
            ->getMock();

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $provider->expects($this->once())
            ->method('deleteAcl')
            ->with($this->identicalTo($oid));
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM acl_classes WHERE class_type = ?',
                ['Test\Class'],
                [ParameterType::STRING]
            );
        $this->connection->expects($this->once())
            ->method('commit');

        $provider->deleteAclClass($oid);
    }

    public function testDeleteAclClassFailure()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some exception');

        $oid = new ObjectIdentity('entity', 'Test\Class');

        $provider = $this->getMockBuilder(MutableAclProvider::class)
            ->onlyMethods(['deleteAcl'])
            ->setConstructorArgs([
                $this->connection,
                $this->createMock(PermissionGrantingStrategyInterface::class),
                ['class_table_name' => 'acl_classes']
            ])
            ->getMock();

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $provider->expects($this->once())
            ->method('deleteAcl')
            ->with($this->identicalTo($oid));
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->willThrowException(new \Exception('some exception'));
        $this->connection->expects($this->once())
            ->method('rollBack');

        $provider->deleteAclClass($oid);
    }

    public function testCacheEmptyAcl(): void
    {
        $oid = new ObjectIdentity('test_id', 'test_type');

        $this->cache->expects($this->once())
            ->method('putInCacheBySids')
            ->with(new Acl(0, $oid, $this->permissionGrantingStrategy, [], false), []);

        $this->provider->cacheEmptyAcl($oid, []);
    }

    public function testClearOidCache(): void
    {
        $oid = new ObjectIdentity('test_id', 'test_type');

        $this->cache->expects($this->once())
            ->method('evictFromCacheByIdentity')
            ->with($oid);

        $this->provider->clearOidCache($oid);
    }

    public function testFindAclsAclNotFoundExceptionThrownForEmptyAncestorIdsAndDbQueriesExecutedOnlyOnce()
    {
        $oid = new ObjectIdentity('(root)', 'entity');
        $sid = new RoleSecurityIdentity('ROLE_TEST');

        $stmt = $this->createMock(ResultStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                'SELECT a.ancestor_id FROM acl_oid_table o INNER JOIN acl_class_table c ON c.id = o.class_id'
                . ' INNER JOIN acl_oid_ancestors_table a ON a.object_identity_id = o.id'
                . ' WHERE (o.object_identifier IN (?) AND c.class_type = ?)',
                [['(root)'], 'entity'],
                [Connection::PARAM_STR_ARRAY, ParameterType::STRING]
            )
            ->willReturn($stmt);

        $exceptionCount = 0;
        try {
            $this->provider->findAcls([$oid], [$sid]);
        } catch (AclNotFoundException $e) {
            $exceptionCount++;
        }
        try {
            $this->provider->findAcls([$oid], [$sid]);
        } catch (AclNotFoundException $e) {
            $exceptionCount++;
        }

        $this->assertEquals(2, $exceptionCount);
    }

    public function testFindAclsShouldUseEmptyAclWhenNonEmptyAncestorIdsAndAclNotFound()
    {
        $oid = new ObjectIdentity('(root)', 'entity');
        $sid = new RoleSecurityIdentity('ROLE_TEST');

        $stmtAncestors = $this->createMock(Statement::class);
        $stmtAncestors->expects($this->once())
            ->method('fetchAll')
            ->willReturn([[1]]);

        $stmtIdentities = $this->createMock(Statement::class);
        $stmtIdentities->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);
        $this->connection->expects($this->exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                [
                    'SELECT a.ancestor_id FROM acl_oid_table o INNER JOIN acl_class_table c ON c.id = o.class_id'
                    . ' INNER JOIN acl_oid_ancestors_table a ON a.object_identity_id = o.id'
                    . ' WHERE (o.object_identifier IN (?) AND c.class_type = ?)',
                    [['(root)'], 'entity'],
                    [Connection::PARAM_STR_ARRAY, ParameterType::STRING]
                ],
                [
                    'SELECT o.id as acl_id, o.object_identifier, o.parent_object_identity_id, o.entries_inheriting,'
                    . ' c.class_type, e.id as ace_id, e.object_identity_id, e.field_name, e.ace_order, e.mask,'
                    . ' e.granting, e.granting_strategy, e.audit_success, e.audit_failure, s.username,'
                    . ' s.identifier as security_identifier FROM acl_oid_table o INNER JOIN acl_class_table c '
                    . 'ON c.id = o.class_id LEFT JOIN acl_entry_table e ON e.class_id = o.class_id '
                    . 'AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL) LEFT JOIN '
                    . 'acl_security_identities s ON s.id = e.security_identity_id '
                    . 'WHERE o.id in (?) AND s.identifier in (?)',
                    [[1], ['ROLE_TEST']],
                    [Connection::PARAM_INT_ARRAY, Connection::PARAM_STR_ARRAY]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $stmtAncestors,
                $stmtIdentities
            );

        $acls = $this->provider->findAcls([$oid], [$sid]);
        $this->assertCount(1, $acls);
        $this->assertSame(0, $acls->offsetGet($oid)->getId(0));
    }
}
