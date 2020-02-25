<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Dbal;

use Doctrine\DBAL\Connection;
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

    protected function setUp()
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
    public function testDeleteSecurityIdentity(SecurityIdentityInterface $sid, $sql)
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->equalTo($sql));
        $this->provider->deleteSecurityIdentity($sid);
    }

    /**
     * @dataProvider updateSecurityIdentityProvider
     */
    public function testUpdateSecurityIdentity(SecurityIdentityInterface $sid, $oldName, $sql)
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->equalTo($sql));
        $this->provider->updateSecurityIdentity($sid, $oldName);
    }

    /**
     * @dataProvider updateSecurityIdentityNoChangesProvider
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateSecurityIdentityShouldThrowInvalidArgumentException(
        SecurityIdentityInterface $sid,
        $oldName
    ) {
        $this->provider->updateSecurityIdentity($sid, $oldName);
    }

    public static function deleteSecurityIdentityProvider()
    {
        return [
            [
                new UserSecurityIdentity('test', 'Acme\User'),
                'DELETE FROM acl_security_identities WHERE identifier = \'Acme\User-test\' AND username = 1'
            ],
            [
                new RoleSecurityIdentity('ROLE_TEST'),
                'DELETE FROM acl_security_identities WHERE identifier = \'ROLE_TEST\' AND username = 0'
            ],
        ];
    }

    public static function updateSecurityIdentityProvider()
    {
        return [
            [
                new UserSecurityIdentity('test', 'Acme\User'),
                'old',
                'UPDATE acl_security_identities SET identifier = \'Acme\User-test\' WHERE '
                . 'identifier = \'Acme\User-old\' AND username = 1'
            ],
            [
                new RoleSecurityIdentity('ROLE_TEST'),
                'ROLE_OLD',
                'UPDATE acl_security_identities SET identifier = \'ROLE_TEST\' WHERE '
                . 'identifier = \'ROLE_OLD\' AND username = 0'
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
            ->method('executeQuery')
            ->with('DELETE FROM acl_classes WHERE class_type = \'Test\\Class\'');
        $this->connection->expects($this->once())
            ->method('commit');

        $provider->deleteAclClass($oid);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage some exception
     */
    public function testDeleteAclClassFailure()
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
            ->method('executeQuery')
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
