<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class MutableAclProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MutableAclProvider */
    private $provider;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var PermissionGrantingStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $permissionGrantingStrategy;

    /** @var AclCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $platform = $platform = $this->getMockBuilder(AbstractPlatform::class)
            ->setMethods(['convertBooleans'])
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

        $this->provider = new MutableAclProvider(
            $this->connection,
            $this->permissionGrantingStrategy,
            ['sid_table_name' => 'acl_security_identities'],
            $this->cache
        );
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
            ->method('executeUpdate')
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
            ->method('executeUpdate')
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

    public static function deleteSecurityIdentityProvider()
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

    public static function updateSecurityIdentityProvider()
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

    public static function updateSecurityIdentityNoChangesProvider()
    {
        return [
            [new UserSecurityIdentity('test', 'Acme\User'), 'test'],
            [new RoleSecurityIdentity('ROLE_TEST'), 'ROLE_TEST'],
        ];
    }

    public function testDeleteAclClass()
    {
        $oid = new ObjectIdentity('entity', 'Test\Class');

        /** @var \PHPUnit\Framework\MockObject\MockObject|MutableAclProvider $provider */
        $provider = $this->getMockBuilder(MutableAclProvider::class)
            ->setMethods(['deleteAcl'])
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
            ->method('executeUpdate')
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

        /** @var \PHPUnit\Framework\MockObject\MockObject|MutableAclProvider $provider */
        $provider = $this->getMockBuilder(MutableAclProvider::class)
            ->setMethods(['deleteAcl'])
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
            ->method('executeUpdate')
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
}
