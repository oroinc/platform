<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class ChainOwnershipMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructionWithoutProviders()
    {
        $chain = new ChainOwnershipMetadataProvider();

        $this->assertAttributeCount(0, 'providers', $chain);
    }

    public function testAddProvider()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider1 */
        $provider1 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider2 */
        $provider2 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias1', $provider1);

        $this->assertAttributeCount(1, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);

        $chain->addProvider('alias2', $provider2);

        $this->assertAttributeCount(2, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);
        $this->assertAttributeContains($provider2, 'providers', $chain);

        $chain->addProvider('alias2', $provider1);

        $this->assertAttributeCount(2, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);
        $this->assertAttributeNotContains($provider2, 'providers', $chain);
    }

    public function testSupports()
    {
        $chain = new ChainOwnershipMetadataProvider();
        $this->assertFalse($chain->supports());

        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        $this->assertFalse($chain->supports());

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        $chain->addProvider('alias2', $this->getMetadataProviderMock(true));
        $this->assertTrue($chain->supports());
    }

    public function testSupportsWithDefault()
    {
        $chain = new ChainOwnershipMetadataProvider();
        $this->assertFalse($chain->supports());

        $default = $this->getMetadataProviderMock(false);
        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $this->assertTrue($chain->supports());
    }

    public function testGetMetadata()
    {
        $metadataFromMockProvider1 = ['label' => 'testLabel1'];
        $metadataFromMockProvider2 = ['label' => 'testLabel2'];

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias1', $this->getMetadataProviderMock(false, $metadataFromMockProvider1));
        $chain->addProvider('alias2', $this->getMetadataProviderMock(true, $metadataFromMockProvider2));

        $result = $chain->getMetadata('stdClass');

        $this->assertInternalType('array', $result);
        $this->assertEquals($metadataFromMockProvider2, $result);
    }

    public function testGetMetadataFromDefault()
    {
        $metadata = ['label' => 'testLabel1'];

        $default = $this->getMetadataProviderMock(true, $metadata);
        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);

        $result = $chain->getMetadata('stdClass');

        $this->assertInternalType('array', $result);
        $this->assertEquals($metadata, $result);
    }

    public function testGetUserClass()
    {
        $userClass = 'Test\User';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects($this->once())
            ->method('getUserClass')
            ->willReturn($userClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $this->assertEquals($userClass, $chain->getUserClass());
    }

    public function testGetBusinessUnitClass()
    {
        $businessUnitClass = 'Test\BusinessUnit';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects($this->once())
            ->method('getBusinessUnitClass')
            ->willReturn($businessUnitClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $this->assertEquals($businessUnitClass, $chain->getBusinessUnitClass());
    }

    public function testGetOrganizationClass()
    {
        $organizationClass = 'Test\Organization';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects($this->once())
            ->method('getOrganizationClass')
            ->willReturn($organizationClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $this->assertEquals($organizationClass, $chain->getOrganizationClass());
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetUserClassWhenSupportedProviderNotFound()
    {
        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getUserClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getUserClass();
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetBusinessUnitClassWhenSupportedProviderNotFound()
    {
        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getBusinessUnitClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getBusinessUnitClass();
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetOrganizationClassWhenSupportedProviderNotFound()
    {
        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getOrganizationClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getOrganizationClass();
    }

    /**
     * @param bool $isSupports
     * @param array $metadata
     * @return OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMetadataProviderMock($isSupports = true, array $metadata = [])
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider */
        $provider = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');
        $provider->expects($this->any())
            ->method('supports')
            ->willReturn($isSupports);
        $provider->expects($isSupports && count($metadata) ? $this->once() : $this->never())
            ->method('getMetadata')
            ->with('stdClass')
            ->willReturn($metadata);

        return $provider;
    }

    public function testGetMaxAccessLevel()
    {
        $accessLevel = AccessLevel::SYSTEM_LEVEL;
        $object = \stdClass::class;

        $provider = $this->getMetadataProviderMock(true);
        $provider->expects($this->once())
            ->method('getMaxAccessLevel')
            ->with($accessLevel, $object)
            ->willReturn($accessLevel);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $this->assertEquals($accessLevel, $chain->getMaxAccessLevel($accessLevel, $object));
    }

    public function testSupportedProvider()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider */
        $provider = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');
        $provider->expects($this->any())->method('supports')->willReturn(true);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $provider->expects($this->once())->method('getUserClass')->willReturn('\stdClass');

        $this->assertEquals('\stdClass', $chain->getUserClass());
    }

    public function testEmulatedProvider()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider */
        $provider = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');
        $provider->expects($this->any())->method('supports')->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $emulated */
        $emulated = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);
        $chain->addProvider('emulated', $emulated);

        $chain->startProviderEmulation('emulated');

        $provider->expects($this->never())->method('getUserClass');
        $emulated->expects($this->once())->method('getUserClass')->willReturn('\stdClass');
        $this->assertEquals('\stdClass', $chain->getUserClass());

        $chain->stopProviderEmulation();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Provider with "alias" alias not registered
     */
    public function testEmulationNotSupported()
    {
        $chain = new ChainOwnershipMetadataProvider();
        $chain->startProviderEmulation('alias');
    }

    public function testClearCache()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider1 */
        $provider1 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider2 */
        $provider2 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $default */
        $default = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects($this->once())->method('clearCache');
        $provider1->expects($this->once())->method('clearCache');
        $default->expects($this->once())->method('clearCache');

        $chain->clearCache();
    }

    public function testWarmUpCache()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider1 */
        $provider1 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $provider2 */
        $provider2 = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $default */
        $default = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects($this->once())->method('warmUpCache');
        $provider1->expects($this->once())->method('warmUpCache');
        $default->expects($this->once())->method('warmUpCache');

        $chain->warmUpCache();
    }
}
