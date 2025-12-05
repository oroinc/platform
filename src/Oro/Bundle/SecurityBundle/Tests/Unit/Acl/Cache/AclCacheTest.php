<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Cache;

use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Oro\Bundle\SecurityBundle\Acl\Event\CacheClearEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheInterface */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PermissionGrantingStrategyInterface */
    private $permissionGrantingStrategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnderlyingAclCache */
    private $underlyingCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SecurityIdentityToStringConverterInterface */
    private $sidConverter;

    private AclCache $aclCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(PhpFileCache::class);
        $this->permissionGrantingStrategy = $this->createMock(PermissionGrantingStrategyInterface::class);
        $this->underlyingCache = $this->createMock(UnderlyingAclCache::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->sidConverter = $this->createMock(SecurityIdentityToStringConverterInterface::class);

        $this->aclCache = new AclCache(
            $this->cache,
            $this->permissionGrantingStrategy,
            $this->underlyingCache,
            $this->eventDispatcher,
            $this->sidConverter
        );

        $this->sidConverter->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function ($sid) {
                return $sid->getRole();
            });
    }

    public function testPutInCache(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not implemented');

        $this->aclCache->putInCache($this->createMock(AclInterface::class));
    }

    public function testGetFromCacheByIdentity(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not implemented');

        $this->aclCache->getFromCacheByIdentity($this->createMock(ObjectIdentityInterface::class));
    }

    public function testGetFromCacheById(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not implemented');

        $this->aclCache->getFromCacheById('testKey');
    }

    public function testEvictFromCacheById(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not implemented');

        $this->aclCache->evictFromCacheById('testKey');
    }

    public function testClearCache(): void
    {
        $this->cache->expects(self::once())
            ->method('clear');
        $this->underlyingCache->expects(self::once())
            ->method('clearCache');
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new CacheClearEvent(), CacheClearEvent::CACHE_CLEAR_EVENT);

        $this->aclCache->clearCache();
    }

    public function testEvictFromCacheByIdentity(): void
    {
        $oid = new ObjectIdentity('entity', \stdClass::class);

        $key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e';

        $sidsItems = ['9a15e9660c1ebc6f429d8' => true, '1fb6be711631380ef1b422ae39' => true];

        $expectedDeletedItems = [
            $key,
            $key . '_9a15e9660c1ebc6f429d8',
            $key . '_1fb6be711631380ef1b422ae39'
        ];

        $this->underlyingCache->expects(self::once())
            ->method('isUnderlying')
            ->with($oid)
            ->willReturn(true);
        $this->underlyingCache->expects(self::once())
            ->method('evictFromCache')
            ->with($oid);

        $cacheItem = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $cacheItem->set($sidsItems);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($key)
            ->willReturn($cacheItem);

        $this->cache->expects(self::once())
            ->method('deleteItems')
            ->with($expectedDeletedItems);

        $this->aclCache->evictFromCacheByIdentity($oid);
    }

    public function testPutInCacheBySidsWithEmptySids(): void
    {
        $acl = new Acl(
            12,
            new ObjectIdentity('entity', \stdClass::class),
            $this->permissionGrantingStrategy,
            [new RoleSecurityIdentity('TEST_ROLE_1')],
            false
        );

        $this->cache->expects(self::never())
            ->method('getItem');
        $this->cache->expects(self::never())
            ->method('save');

        $this->aclCache->putInCacheBySids($acl, []);
    }

    public function testPutInCacheBySids(): void
    {
        $key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e';
        $item1Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_a0a2c4e7b89e4e9a3244e64be56ec92b';
        $item2Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_4d48b7d84eef3d4be900edfdb843e290';
        $item3Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_7e0e25e9f99ddb6ed17b43b11490776f';

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');
        $sid2 = new RoleSecurityIdentity('TEST_ROLE_2');
        $sid3 = new RoleSecurityIdentity('TEST_ROLE_3');

        $acl = new Acl(12, $oid, $this->permissionGrantingStrategy, [$sid1, $sid2], false);
        $acl->insertClassAce($sid1, 0, 0, true);
        $acl->insertObjectAce($sid2, 1, 0, true);
        $acl->insertClassFieldAce('field1', $sid1, 2, 0, true);
        $acl->insertObjectFieldAce('field1', $sid2, 3, 0, true);

        $item1 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item2 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item3 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $sidsItem = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $sidsItem->set([]);

        $this->cache->expects(self::exactly(4))
            ->method('getItem')
            ->willReturnMap([
                [$item1Key, $item1],
                [$item2Key, $item2],
                [$item3Key, $item3],
                [$key, $sidsItem],
            ]);
        $this->cache->expects(self::exactly(4))
            ->method('save')
            ->willReturnMap([
                [$item1, true],
                [$item2, true],
                [$item3, true],
                [$sidsItem, true],
            ]);

        $this->aclCache->putInCacheBySids($acl, [$sid1, $sid2, $sid3]);

        self::assertEquals(
            [
                'a0a2c4e7b89e4e9a3244e64be56ec92b',
                [
                    'c' => [['mask' => 0, 'strategy' => 'all']],
                    'fc' => ['field1' => [['mask' => 2, 'strategy' => 'all']]]
                ]
            ],
            $item1->get()
        );

        self::assertEquals(
            [
                '4d48b7d84eef3d4be900edfdb843e290',
                [
                    'o' => [['mask' => 1, 'strategy' => 'all']],
                    'fo' => ['field1' => [['mask' => 3, 'strategy' => 'all']]]
                ]
            ],
            $item2->get()
        );

        self::assertEquals(
            ['7e0e25e9f99ddb6ed17b43b11490776f', []],
            $item3->get()
        );

        self::assertEquals(
            [
                'a0a2c4e7b89e4e9a3244e64be56ec92b' => true,
                '4d48b7d84eef3d4be900edfdb843e290' => true,
                '7e0e25e9f99ddb6ed17b43b11490776f' => true
            ],
            $sidsItem->get()
        );
    }

    public function testGetFromCacheByIdentityAndSidsWithEmptySids(): void
    {
        $oid = new ObjectIdentity('entity', \stdClass::class);

        $this->cache->expects(self::never())
            ->method('getItems');

        self::assertNull($this->aclCache->getFromCacheByIdentityAndSids($oid, []));
    }

    public function testGetFromCacheByIdentityAndSids(): void
    {
        $item1Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_a0a2c4e7b89e4e9a3244e64be56ec92b';
        $item2Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_4d48b7d84eef3d4be900edfdb843e290';
        $item3Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_7e0e25e9f99ddb6ed17b43b11490776f';

        $item1 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item1->set([
            'a0a2c4e7b89e4e9a3244e64be56ec92b',
            [
                'c' => [['mask' => 0, 'strategy' => 'all']],
                'fc' => ['field1' => [['mask' => 2, 'strategy' => 'all']]]
            ]
        ]);
        $item2 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item2->set([
            '4d48b7d84eef3d4be900edfdb843e290',
            [
                'o' => [['mask' => 1, 'strategy' => 'all']],
                'fo' => ['field1' => [['mask' => 3, 'strategy' => 'all']]]
            ]
        ]);
        $item3 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item3->set([]);

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');
        $sid2 = new RoleSecurityIdentity('TEST_ROLE_2');
        $sid3 = new RoleSecurityIdentity('TEST_ROLE_3');

        $acl = new Acl(-1, $oid, $this->permissionGrantingStrategy, [$sid1, $sid2, $sid3], false);
        $acl->insertClassAce($sid1, 0, 0, true, 'all');
        $acl->insertObjectAce($sid2, 1, 0, true, 'all');
        $acl->insertClassFieldAce('field1', $sid1, 2, 0, true, 'all');
        $acl->insertObjectFieldAce('field1', $sid2, 3, 0, true, 'all');

        $this->cache->expects(self::once())
            ->method('getItems')
            ->with([$item1Key, $item2Key, $item3Key])
            ->willReturn([$item1, $item2, $item3]);

        self::assertEquals($acl, $this->aclCache->getFromCacheByIdentityAndSids($oid, [$sid1, $sid2, $sid3]));
    }

    public function testGetFromCacheByIdentityAndSidsWhenSidWasNotCached(): void
    {
        $item1Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_a0a2c4e7b89e4e9a3244e64be56ec92b';
        $item2Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_4d48b7d84eef3d4be900edfdb843e290';
        $item3Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_7e0e25e9f99ddb6ed17b43b11490776f';

        $item1 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item1->set([
            'a0a2c4e7b89e4e9a3244e64be56ec92b',
            [
                'c' => [['mask' => 0, 'strategy' => 'all']],
                'fc' => ['field1' => [['mask' => 2, 'strategy' => 'all']]]
            ]
        ]);
        $item2 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item2->set([
            '4d48b7d84eef3d4be900edfdb843e290',
            [
                'o' => [['mask' => 1, 'strategy' => 'all']],
                'fo' => ['field1' => [['mask' => 3, 'strategy' => 'all']]]
            ]
        ]);
        $item3 = $this->getEntity(CacheItem::class, ['isHit' => false]);

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');
        $sid2 = new RoleSecurityIdentity('TEST_ROLE_2');
        $sid3 = new RoleSecurityIdentity('TEST_ROLE_3');

        $this->cache->expects(self::once())
            ->method('getItems')
            ->with([$item1Key, $item2Key, $item3Key])
            ->willReturn([$item1, $item2, $item3]);

        self::assertNull($this->aclCache->getFromCacheByIdentityAndSids($oid, [$sid1, $sid2, $sid3]));
    }

    public function testGetFromCacheByIdentityAndSidsWithBackwardCompatibility(): void
    {
        $item1Key = '09a15e9660c1ebc6f429d818825ce0c62f82758dcc11e2a2f05e2a7f35a1b34319985aa1'
            . '_f5e638cc78dd325906c1298a0c21fb6be711631380ef1b422ae392db3ca08b8e061aea4e'
            . '_a0a2c4e7b89e4e9a3244e64be56ec92b';

        // Old cache format with only mask (int)
        $item1 = $this->getEntity(CacheItem::class, ['isHit' => true]);
        $item1->set(['a0a2c4e7b89e4e9a3244e64be56ec92b', ['c' => [0], 'o' => [1]]]);

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');

        $acl = new Acl(-1, $oid, $this->permissionGrantingStrategy, [$sid1], false);
        $acl->insertClassAce($sid1, 0, 0, true, 'all');
        $acl->insertObjectAce($sid1, 1, 0, true, 'all');

        $this->cache->expects(self::once())
            ->method('getItems')
            ->with([$item1Key])
            ->willReturn([$item1]);

        self::assertEquals($acl, $this->aclCache->getFromCacheByIdentityAndSids($oid, [$sid1]));
    }
}
