<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache;
use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\FullAccessFieldRootAclBuilder;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootAclWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverter;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class RootBasedAclProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MutableAclProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $baseProvider;

    /** @var PermissionGrantingStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $strategy;

    /** @var UnderlyingAclCache|\PHPUnit\Framework\MockObject\MockObject */
    private $underlyingCache;

    /** @var FullAccessFieldRootAclBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $fullAccessFieldRootAclBuilder;

    /** @var RootBasedAclProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->baseProvider = $this->createMock(MutableAclProvider::class);
        $this->strategy = $this->createMock(PermissionGrantingStrategyInterface::class);
        $this->underlyingCache = $this->createMock(UnderlyingAclCache::class);
        $this->fullAccessFieldRootAclBuilder = $this->createMock(FullAccessFieldRootAclBuilder::class);

        $this->provider = new RootBasedAclProvider(
            new ObjectIdentityFactory(
                TestHelper::get($this)->createAclExtensionSelector()
            ),
            new SecurityIdentityToStringConverter(),
            $this->fullAccessFieldRootAclBuilder
        );
        $this->provider->setBaseAclProvider($this->baseProvider);
        $this->provider->setUnderlyingCache($this->underlyingCache);
    }

    public function testFindChildren()
    {
        $oid = new ObjectIdentity(123, 'Test');
        $this->baseProvider->expects($this->once())
            ->method('findChildren')
            ->with($this->identicalTo($oid), $this->equalTo(true))
            ->willReturn([]);

        $this->assertEquals([], $this->provider->findChildren($oid, true));
    }

    public function testFindAcls()
    {
        $oids = [new ObjectIdentity(123, 'Test')];
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $this->baseProvider->expects($this->once())
            ->method('findAcls')
            ->with($this->equalTo($oids), $this->equalTo($sids))
            ->willReturn([]);

        $this->assertEquals([], $this->provider->findAcls($oids, $sids));
    }

    public function testFindAclWithRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $rootOid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $acl = $this->getAcl($oid);
        $rootAcl = $this->getAcl($rootOid);

        $this->underlyingCache->expects($this->once())
            ->method('isUnderlying')
            ->with($this->identicalTo($oid))
            ->willReturn(false);
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($acl))
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');
        $this->fullAccessFieldRootAclBuilder->expects($this->once())
            ->method('fillFieldRootAces')
            ->with($rootAcl, $sids);

        $this->setFindAclExpectation([
            $this->getOidKey($oid)     => $acl,
            $this->getOidKey($rootOid) => $rootAcl
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper(
                $acl,
                new RootAclWrapper($rootAcl, new SecurityIdentityToStringConverter())
            ),
            $resultAcl
        );
    }

    public function testFindAclWithNoRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $acl = $this->getAcl($oid);

        $this->underlyingCache->expects($this->once())
            ->method('isUnderlying')
            ->with($this->identicalTo($oid))
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('isEmptyAcl');
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');

        $this->setFindAclExpectation([
            $this->getOidKey($oid) => $acl
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($acl, $resultAcl);
    }

    public function testFindAclWithNoAclAndUnderlyingAndRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $rootOid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $rootAcl = $this->getAcl($rootOid);
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturn(false);
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($underlyingAcl))
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->once())
            ->method('cacheUnderlying')
            ->with($oid);
        $this->fullAccessFieldRootAclBuilder->expects($this->once())
            ->method('fillFieldRootAces')
            ->with($rootAcl, $sids);

        $this->setFindAclExpectation([
            $this->getOidKey($rootOid)       => $rootAcl,
            $this->getOidKey($underlyingOid) => $underlyingAcl
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper(
                $underlyingAcl,
                new RootAclWrapper($rootAcl, new SecurityIdentityToStringConverter())
            ),
            $resultAcl
        );
    }

    public function testFindAclWithReplaceWithUnderlyingAndRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $rootOid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $acl = $this->getAcl($oid);
        $rootAcl = $this->getAcl($rootOid);
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturnCallback(function (ObjectIdentity $obj) use ($oid, $underlyingOid) {
                return match ($obj->getIdentifier()) {
                    $oid->getIdentifier() => true,
                    $underlyingOid->getIdentifier() => false
                };
            });
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($underlyingAcl))
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');
        $this->fullAccessFieldRootAclBuilder->expects($this->once())
            ->method('fillFieldRootAces')
            ->with($rootAcl, $sids);

        $this->setFindAclExpectation([
            $this->getOidKey($oid)           => $acl,
            $this->getOidKey($rootOid)       => $rootAcl,
            $this->getOidKey($underlyingOid) => $underlyingAcl
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper(
                $underlyingAcl,
                new RootAclWrapper($rootAcl, new SecurityIdentityToStringConverter())
            ),
            $resultAcl
        );
    }

    public function testFindAclWithNoAclAndUnderlyingAndNoRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('isEmptyAcl');
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->once())
            ->method('cacheUnderlying')
            ->with($oid);
        $this->fullAccessFieldRootAclBuilder->expects($this->never())
            ->method('fillFieldRootAces');

        $this->setFindAclExpectation([
            $this->getOidKey($underlyingOid) => $underlyingAcl
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($underlyingAcl, $resultAcl);
    }

    public function testFindAclWithNoAclAndNoUnderlyingAndRoot()
    {
        $sids = [$this->createMock(SecurityIdentityInterface::class)];
        $oid = new ObjectIdentity(123, 'Test');
        $rootOid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->getAcl($rootOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturn(false);
        $this->baseProvider->expects($this->never())
            ->method('isEmptyAcl');
        $this->baseProvider->expects($this->once())
            ->method('cacheEmptyAcl')
            ->with($oid);
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');
        $this->fullAccessFieldRootAclBuilder->expects($this->never())
            ->method('fillFieldRootAces');

        $this->setFindAclExpectation([
            $this->getOidKey($rootOid) => $rootAcl,
        ]);

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($rootAcl, $resultAcl);
    }

    private function getOidKey(ObjectIdentity $oid): string
    {
        return $oid->getIdentifier() . $oid->getType();
    }

    private function getAcl(ObjectIdentity $oid, int $id = 1): Acl
    {
        return new Acl($id, $oid, $this->strategy, [], false);
    }

    private function setFindAclExpectation(array $foundAcls)
    {
        $this->baseProvider->expects($this->any())
            ->method('findAcl')
            ->willReturnCallback(function ($oid, $sids) use ($foundAcls) {
                if (isset($foundAcls[$this->getOidKey($oid)])) {
                    return $foundAcls[$this->getOidKey($oid)];
                }
                throw new AclNotFoundException('Acl not found');
            });
    }
}
