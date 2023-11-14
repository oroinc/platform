<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Cache\CacheInstantiatorInterface;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheUserInfoProvider;
use Oro\Bundle\UserBundle\Entity\User;

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

        $namespacesCache = $this->createMock(CacheProvider::class);
        $cache = $this->createMock(CacheProvider::class);

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
            ->method('fetch')
            ->with('User_2')
            ->willReturn([1253 => true, 1254 => true, 1255 => true]);

        self::assertSame($cache, $this->aclCacheProvider->getCurrentUserCache());
    }

    public function testGetCurrentUserCacheWithNotKnownCache(): void
    {
        $user = new User();
        $user->setId(1254);

        $namespacesCache = $this->createMock(CacheProvider::class);
        $cache = $this->createMock(CacheProvider::class);

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
            ->method('fetch')
            ->willReturnMap([
                ['User_2', []],
                ['itemsList', []]
            ]);

        $namespacesCache->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                ['User_2', [1254 => true]],
                ['itemsList', [User::class => [2]]]
            );

        self::assertSame($cache, $this->aclCacheProvider->getCurrentUserCache());
    }

    public function testClear(): void
    {
        $namespacesCache = $this->createMock(CacheProvider::class);
        $namespacesCache->expects(self::once())
            ->method('deleteAll');
        $cache11 = $this->createMock(CacheProvider::class);
        $cache11->expects(self::once())
            ->method('deleteAll');
        $cache13 = $this->createMock(CacheProvider::class);
        $cache13->expects(self::once())
            ->method('deleteAll');
        $cache244 = $this->createMock(CacheProvider::class);
        $cache244->expects(self::once())
            ->method('deleteAll');

        $this->cacheInstantiator->expects(self::exactly(4))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_namespaces', $namespacesCache],
                ['doctrine_acl_TestEntity1_1', $cache11],
                ['doctrine_acl_TestEntity1_3', $cache13],
                ['doctrine_acl_TestEntity2_44', $cache244]
            ]);

        $batchListItem = [
            'Acme\TestBundle\Entity\TestEntity1' => [1, 2],
            'Acme\TestBundle\Entity\TestEntity2' => [1]
        ];

        $batchItem11 = [1 => true, 3 => true];
        $batchItem12 = [];
        $batchItem21 = [44 => true];

        $namespacesCache->expects(self::exactly(4))
            ->method('fetch')
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
        $namespacesCache = $this->createMock(CacheProvider::class);
        $cache = $this->createMock(CacheProvider::class);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1255', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::once())
            ->method('fetch')
            ->with('User_2')
            ->willReturn([1254 => true, 1255 => true, 1256 => true]);

        $namespacesCache->expects(self::once())
            ->method('save')
            ->with('User_2', [1254 => true,1256 => true]);

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }

    public function testClearForEntityWhenItemLastInBatch(): void
    {
        $user = new User();
        $user->setId(1255);

        $namespacesCache = $this->createMock(CacheProvider::class);
        $cache = $this->createMock(CacheProvider::class);

        $this->cacheInstantiator->expects(self::exactly(2))
            ->method('getCacheInstance')
            ->willReturnMap([
                ['doctrine_acl_User_1255', $cache],
                ['doctrine_acl_namespaces', $namespacesCache]
            ]);

        $namespacesCache->expects(self::exactly(2))
            ->method('fetch')
            ->willReturnMap([
                ['User_2', [1255 => true]],
                [
                    'itemsList',
                    [
                        User::class => [1, 2, 3],
                        \stdClass::class => [1, 2, 3, 4]
                    ]
                ]
            ]);

        $namespacesCache->expects(self::once())
            ->method('delete')
            ->with('User_2');
        $namespacesCache->expects(self::once())
            ->method('save')
            ->with(
                'itemsList',
                [
                    User::class => [0 => 1, 2 => 3],
                    \stdClass::class => [1, 2, 3, 4]
                ]
            );

        $this->aclCacheProvider->clearForEntity(User::class, 1255);
    }
}
