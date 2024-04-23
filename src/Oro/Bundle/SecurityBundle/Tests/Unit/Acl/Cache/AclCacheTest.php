<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Acl\Cache\AclCache;
use Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverterInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

class AclCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheProvider */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PermissionGrantingStrategyInterface */
    private $permissionGrantingStrategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnderlyingAclCache */
    private $underlyingCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SecurityIdentityToStringConverterInterface */
    private $sidConverter;

    private $aclCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);
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
            ->method('deleteAll');
        $this->underlyingCache->expects(self::once())
            ->method('clearCache');
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with('oro_security.acl_cache.clear');

        $this->aclCache->clearCache();
    }

    public function testEvictFromCacheByIdentity(): void
    {
        $oid = new ObjectIdentity('entity', \stdClass::class);

        $key = '09a15e9660c1ebc6f429d818825ce0c6_f5e638cc78dd325906c1298a0c21fb6b';

        $sidsItems = ['9a15e9660c1ebc6f429d8' => true, '1fb6be711631380ef1b422ae39' => true];

        $this->underlyingCache->expects(self::once())
            ->method('isUnderlying')
            ->with($oid)
            ->willReturn(true);
        $this->underlyingCache->expects(self::once())
            ->method('evictFromCache')
            ->with($oid);

        $this->cache->expects(self::once())
            ->method('contains')
            ->with($key)
            ->willReturn(true);

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with($key)
            ->willReturn($sidsItems);

        $this->cache->expects(self::exactly(3))
            ->method('delete')
            ->withConsecutive(
                [$key . '_9a15e9660c1ebc6f429d8'],
                [$key . '_1fb6be711631380ef1b422ae39'],
                [$key]
            );

        $this->aclCache->evictFromCacheByIdentity($oid);
    }

    public function testPutInCacheBySids(): void
    {
        $sidKey = 'b8c1a3069167247e3503f0daba6c5723';
        $key = '09a15e9660c1ebc6f429d818825ce0c6_f5e638cc78dd325906c1298a0c21fb6b';
        $itemKey = '09a15e9660c1ebc6f429d818825ce0c6_f5e638cc78dd325906c1298a0c21fb6b'
            . '_b8c1a3069167247e3503f0daba6c5723';

        $expectedSerializedString = file_get_contents(__DIR__ . '/testSerializedData.bin');

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');
        $sid2 = new RoleSecurityIdentity('TEST_ROLE_2');
        $sid3 = new RoleSecurityIdentity('TEST_ROLE_3');

        $acl = new Acl(12, $oid, $this->permissionGrantingStrategy, [$sid1, $sid2], false);
        $acl->insertClassAce($sid1, 0, 0, true);
        $acl->insertObjectAce($sid2, 1, 0, true);
        $acl->insertClassFieldAce('field1', $sid1, 2, 0, true);
        $acl->insertObjectFieldAce('field1', $sid2, 3, 0, true);

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with($key)
            ->willReturn(false);

        $this->cache->expects(self::exactly(2))
            ->method('save')
            ->willReturnMap([
                [$itemKey, $expectedSerializedString, true],
                [$key, [$sidKey => true], true],
            ]);

        $this->aclCache->putInCacheBySids($acl, [$sid1, $sid2, $sid3]);
    }

    public function testGetFromCacheByIdentityAndSids(): void
    {
        $itemKey = '09a15e9660c1ebc6f429d818825ce0c6_f5e638cc78dd325906c1298a0c21fb6b'
            . '_b8c1a3069167247e3503f0daba6c5723';

        $oid = new ObjectIdentity('entity', \stdClass::class);
        $sid1 = new RoleSecurityIdentity('TEST_ROLE_1');
        $sid2 = new RoleSecurityIdentity('TEST_ROLE_2');
        $sid3 = new RoleSecurityIdentity('TEST_ROLE_3');

        $serializedString = file_get_contents(__DIR__ . '/testSerializedData.bin');

        $acl = new Acl(12, $oid, $this->permissionGrantingStrategy, [$sid1, $sid2], false);
        $acl->insertClassAce($sid1, 0, 0, true);
        $acl->insertObjectAce($sid2, 1, 0, true);
        $acl->insertClassFieldAce('field1', $sid1, 2, 0, true);
        $acl->insertObjectFieldAce('field1', $sid2, 3, 0, true);

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with($itemKey)
            ->willReturn($serializedString);

        self::assertEquals($acl, $this->aclCache->getFromCacheByIdentityAndSids($oid, [$sid1, $sid2, $sid3]));
    }
}
