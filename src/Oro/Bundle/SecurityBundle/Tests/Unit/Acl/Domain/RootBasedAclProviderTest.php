<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

class RootBasedAclProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RootBasedAclProvider */
    private $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $baseProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $strategy;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $underlyingCache;

    protected function setUp()
    {
        $this->strategy = $this->getMockForAbstractClass(
            'Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface'
        );

        $this->baseProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider     = new RootBasedAclProvider(
            new ObjectIdentityFactory(
                TestHelper::get($this)->createAclExtensionSelector()
            )
        );
        $this->provider->setBaseAclProvider($this->baseProvider);
        $this->underlyingCache = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider->setUnderlyingCache($this->underlyingCache);
    }

    public function testFindChildren()
    {
        $oid = new ObjectIdentity(123, 'Test');
        $this->baseProvider->expects($this->once())
            ->method('findChildren')
            ->with($this->identicalTo($oid), $this->equalTo(true))
            ->will($this->returnValue(array()));

        $this->assertEquals(array(), $this->provider->findChildren($oid, true));
    }

    public function testFindAcls()
    {
        $oids = array(new ObjectIdentity(123, 'Test'));
        $sids = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $this->baseProvider->expects($this->once())
            ->method('findAcls')
            ->with($this->equalTo($oids), $this->equalTo($sids))
            ->will($this->returnValue(array()));

        $this->assertEquals(array(), $this->provider->findAcls($oids, $sids));
    }

    public function testFindAclWithRoot()
    {
        $sids    = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid     = new ObjectIdentity(123, 'Test');
        $rootOid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $acl     = $this->getAcl($oid);
        $rootAcl = $this->getAcl($rootOid);

        $this->underlyingCache->expects($this->once())
            ->method('isUnderlying')
            ->with($this->identicalTo($oid))
            ->will($this->returnValue(false));
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($acl))
            ->will($this->returnValue(false));
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');

        $this->setFindAclExpectation(
            [
                $this->getOidKey($oid)     => $acl,
                $this->getOidKey($rootOid) => $rootAcl
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper($acl, $rootAcl),
            $resultAcl
        );
    }

    public function testFindAclWithNoRoot()
    {
        $sids = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid  = new ObjectIdentity(123, 'Test');
        $acl  = $this->getAcl($oid);

        $this->underlyingCache->expects($this->once())
            ->method('isUnderlying')
            ->with($this->identicalTo($oid))
            ->will($this->returnValue(false));
        $this->baseProvider->expects($this->never())
            ->method('isEmptyAcl');
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');

        $this->setFindAclExpectation(
            [
                $this->getOidKey($oid) => $acl
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($acl, $resultAcl);
    }

    public function testFindAclWithNoAclAndUnderlyingAndRoot()
    {
        $sids          = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid           = new ObjectIdentity(123, 'Test');
        $rootOid       = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $rootAcl       = $this->getAcl($rootOid);
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturnMap(
                [
                    [$underlyingOid, false],
                    [$oid, false],
                ]
            );
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($underlyingAcl))
            ->will($this->returnValue(false));
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->once())
            ->method('cacheUnderlying')
            ->with($oid);

        $this->setFindAclExpectation(
            [
                $this->getOidKey($rootOid)       => $rootAcl,
                $this->getOidKey($underlyingOid) => $underlyingAcl
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper($underlyingAcl, $rootAcl),
            $resultAcl
        );
    }

    public function testFindAclWithReplaceWithUnderlyingAndRoot()
    {
        $sids          = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid           = new ObjectIdentity(123, 'Test');
        $rootOid       = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $acl           = $this->getAcl($oid);
        $rootAcl       = $this->getAcl($rootOid);
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturnMap(
                [
                    [$underlyingOid, false],
                    [$oid, true],
                ]
            );
        $this->baseProvider->expects($this->once())
            ->method('isEmptyAcl')
            ->with($this->identicalTo($underlyingAcl))
            ->will($this->returnValue(false));
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->never())
            ->method('cacheUnderlying');

        $this->setFindAclExpectation(
            [
                $this->getOidKey($oid)           => $acl,
                $this->getOidKey($rootOid)       => $rootAcl,
                $this->getOidKey($underlyingOid) => $underlyingAcl
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals(
            new RootBasedAclWrapper($underlyingAcl, $rootAcl),
            $resultAcl
        );
    }

    public function testFindAclWithNoAclAndUnderlyingAndNoRoot()
    {
        $sids          = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid           = new ObjectIdentity(123, 'Test');
        $underlyingOid = new ObjectIdentity('entity', 'Test');
        $underlyingAcl = $this->getAcl($underlyingOid);

        $this->underlyingCache->expects($this->any())
            ->method('isUnderlying')
            ->willReturnMap(
                [
                    [$underlyingOid, false],
                    [$oid, false],
                ]
            );
        $this->baseProvider->expects($this->never())
            ->method('isEmptyAcl');
        $this->baseProvider->expects($this->never())
            ->method('cacheEmptyAcl');
        $this->underlyingCache->expects($this->once())
            ->method('cacheUnderlying')
            ->with($oid);

        $this->setFindAclExpectation(
            [
                $this->getOidKey($underlyingOid) => $underlyingAcl
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($underlyingAcl, $resultAcl);
    }

    public function testFindAclWithNoAclAndNoUnderlyingAndRoot()
    {
        $sids          = array($this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface'));
        $oid           = new ObjectIdentity(123, 'Test');
        $rootOid       = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl       = $this->getAcl($rootOid);

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

        $this->setFindAclExpectation(
            [
                $this->getOidKey($rootOid) => $rootAcl,
            ]
        );

        $resultAcl = $this->provider->findAcl($oid, $sids);
        $this->assertEquals($rootAcl, $resultAcl);
    }

    protected function getOidKey(ObjectIdentity $oid)
    {
        return $oid->getIdentifier() . $oid->getType();
    }

    protected function getAcl(ObjectIdentity $oid, $id = 1)
    {
        return new Acl($id, $oid, $this->strategy, [], false);
    }

    protected function setFindAclExpectation($findAcl)
    {
        $this->baseProvider->expects($this->any())
            ->method('findAcl')
            ->will(
                $this->returnCallback(
                    function ($oid, $sids) use ($findAcl) {
                        if (isset($findAcl[$this->getOidKey($oid)])) {
                            return $findAcl[$this->getOidKey($oid)];
                        }
                        throw new AclNotFoundException('Acl not found');
                    }
                )
            );
    }
}
