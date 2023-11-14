<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Cache;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Cache\CacheInstantiatorInterface;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheUserInfoProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DoctrineAclCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var CacheInstantiatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheInstantiator;

    protected DoctrineAclCacheProvider $aclCacheProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->cacheInstantiator = $this->createMock(CacheInstantiatorInterface::class);

        $this->aclCacheProvider = new DoctrineAclCacheProvider(
            $this->cacheInstantiator,
            new DoctrineAclCacheUserInfoProvider($this->tokenAccessor)
        );
    }

    public function testGetCurrentUserCacheWithKnownCache(): void
    {
        $user = new User();
        $user->setId(1254);

        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = $this->createMock(ItemInterface::class);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1254', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::once())
            ->method('getItem')
            ->with('User_2')
            ->willReturn($batchItem);

        $batchItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem->expects(self::once())
            ->method('get')
            ->willReturn([1253 => true, 1254 => true, 1255 => true]);

        self::assertSame($cache, $this->aclCacheProvider->getCurrentUserCache());
    }

    public function testGetCurrentUserCacheWithNotKnownCache(): void
    {
        $user = new User();
        $user->setId(1254);

        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = $this->createMock(ItemInterface::class);
        $batchListItem = $this->createMock(ItemInterface::class);

        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1254', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::exactly(2))
            ->method('getItem')
            ->willReturnMap([
                ['User_2', $batchItem],
                ['itemsList', $batchListItem]
            ]);

        $namespacesCache->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [$batchItem],
                [$batchListItem]
            )->willReturn(true);

        $batchItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $batchItem->expects(self::once())
            ->method('set')
            ->willReturn([1254 => true]);

        $batchListItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $batchListItem->expects(self::once())
            ->method('set')
            ->willReturn([User::class => [2]]);

        self::assertSame($cache, $this->aclCacheProvider->getCurrentUserCache());
    }

    public function testClear(): void
    {
        $namespacesCache = $this->createMock(AdapterInterface::class);
        $namespacesCache->expects(self::once())
            ->method('clear');
        $cache11 = $this->createMock(AdapterInterface::class);
        $cache11->expects(self::once())
            ->method('clear');
        $cache13 = $this->createMock(AdapterInterface::class);
        $cache13->expects(self::once())
            ->method('clear');
        $cache244 = $this->createMock(AdapterInterface::class);
        $cache244->expects(self::once())
            ->method('clear');

        $this->cacheInstantiator->expects(self::exactly(4))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_namespaces', $namespacesCache],
                ['doctrine_acl_TestEntity1_1', $cache11],
                ['doctrine_acl_TestEntity1_3', $cache13],
                ['doctrine_acl_TestEntity2_44', $cache244]
            ]);

        $batchListItem = $this->createMock(ItemInterface::class);
        $batchListItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchListItem->expects(self::once())
            ->method('get')
            ->willReturn([
                'Acme\TestBundle\Entity\TestEntity1' => [1, 2],
                'Acme\TestBundle\Entity\TestEntity2' => [1]
            ]);

        $batchItem11 = $this->createMock(ItemInterface::class);
        $batchItem11->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem11->expects(self::once())
            ->method('get')
            ->willReturn([1 => true, 3 => true]);
        $batchItem12 = $this->createMock(ItemInterface::class);
        $batchItem12->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem12->expects(self::once())
            ->method('get')
            ->willReturn([]);
        $batchItem21 = $this->createMock(ItemInterface::class);
        $batchItem21->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem21->expects(self::once())
            ->method('get')
            ->willReturn([44 => true]);

        $namespacesCache->expects(self::exactly(4))
            ->method('getItem')
            ->willReturnMap([
                ['itemsList', $batchListItem],
                ['TestEntity1_1', $batchItem11],
                ['TestEntity1_2', $batchItem12],
                ['TestEntity2_1', $batchItem21]
            ]);

        $this->aclCacheProvider->clear();
    }

    public function testClearForEntityWhenItemNotLastInBatch(): void
    {
        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = $this->createMock(ItemInterface::class);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1255', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::once())
            ->method('getItem')
            ->with('User_2')
            ->willReturn($batchItem);

        $namespacesCache->expects(self::once())
            ->method('save')
            ->with($batchItem);

        $batchItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem->expects(self::once())
            ->method('get')
            ->willReturn([1254 => true, 1255 => true, 1256 => true]);
        $batchItem->expects(self::once())
            ->method('set')
            ->with([1254 => true,1256 => true]);

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }

    public function testClearForEntityWhenItemLastInBatch(): void
    {
        $user = new User();
        $user->setId(1255);

        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = $this->createMock(ItemInterface::class);
        $batchListItem = $this->createMock(ItemInterface::class);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1255', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::exactly(2))
            ->method('getItem')
            ->willReturnMap([
                ['User_2', $batchItem],
                ['itemsList', $batchListItem]
            ]);

        $namespacesCache->expects(self::once())
            ->method('deleteItem')
            ->with('User_2');
        $namespacesCache->expects(self::once())
            ->method('save')
            ->with($batchListItem);

        $batchItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchItem->expects(self::once())
            ->method('get')
            ->willReturn([1255 => true]);

        $batchListItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $batchListItem->expects(self::once())
            ->method('get')
            ->willReturn([
                User::class => [1, 2, 3],
                \stdClass::class => [1, 2, 3, 4]
            ]);
        $batchListItem->expects(self::once())
            ->method('set')
            ->with([
                User::class => [0 => 1, 2 => 3],
                \stdClass::class => [1, 2, 3, 4]
            ]);

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }
}
