<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Cache;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Cache\CacheInstantiatorInterface;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheUserInfoProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

class DoctrineAclCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var CacheInstantiatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheInstantiator;

    protected DoctrineAclCacheProvider $aclCacheProvider;

    #[\Override]
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
            ->willReturn($batchItem = new CacheItem());

        $isHitReflection = new \ReflectionProperty($batchItem, 'isHit');
        $isHitReflection->setValue($batchItem, true);
        $batchItem->set(
            [
                1253 => true,
                1254 => true,
                1255 => true,
            ]
        );

        self::assertSame($cache, $this->aclCacheProvider->getCurrentUserCache());
    }

    public function testGetCurrentUserCacheWithNotKnownCache(): void
    {
        $user = new User();
        $user->setId(1254);

        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = new CacheItem();
        $batchListItem = new CacheItem();

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

        $batchItem->set([1254 => true]);
        $batchListItem->set([[User::class => [2]]]);

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

        $batchListItem = new CacheItem();
        $isHitReflection = new \ReflectionProperty($batchListItem, 'isHit');
        $isHitReflection->setValue($batchListItem, true);
        $batchListItem->set(
            [
                'Acme\TestBundle\Entity\TestEntity1' => [1, 2],
                'Acme\TestBundle\Entity\TestEntity2' => [1],
            ]
        );

        $batchItem11 = new CacheItem();
        $isHitReflection->setValue($batchItem11, true);
        $batchItem11->set([1 => true, 3 => true]);

        $batchItem12 = new CacheItem();
        $isHitReflection->setValue($batchItem12, true);
        $batchItem12->set([]);

        $batchItem21 = new CacheItem();
        $isHitReflection->setValue($batchItem21, true);
        $batchItem21->set([44 => true]);

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

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1255', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::once())
            ->method('getItem')
            ->with('User_2')
            ->willReturn($batchItem = new CacheItem());

        $batchItem = new CacheItem();
        $isHitReflection = new \ReflectionProperty($batchItem, 'isHit');
        $isHitReflection->setValue($batchItem, true);
        $batchItem->set(
            [
                1254 => true,
                1255 => true,
                1256 => true,
            ]
        );

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }

    public function testClearForEntityWhenItemLastInBatch(): void
    {
        $user = new User();
        $user->setId(1255);

        $namespacesCache = $this->createMock(AdapterInterface::class);
        $cache = $this->createMock(AdapterInterface::class);
        $batchItem = new CacheItem();
        $batchListItem = new CacheItem();

        $isHitReflection = new \ReflectionProperty($batchItem, 'isHit');

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

        $isHitReflection->setValue($batchItem, true);
        $batchItem->set([1255 => true]);

        $isHitReflection->setValue($batchListItem, true);
        $batchListItem->set(
            [
                User::class => [1, 2, 3],
                \stdClass::class => [1, 2, 3, 4],
            ]
        );

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }
}
