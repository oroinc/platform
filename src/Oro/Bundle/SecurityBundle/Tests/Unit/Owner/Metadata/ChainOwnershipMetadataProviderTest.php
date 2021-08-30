<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChainOwnershipMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testAddProvider()
    {
        $supports1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $supports1->expects($this->once())
            ->method('supports');

        $chain1 = new ChainOwnershipMetadataProvider();
        $chain1->addProvider('alias1', $supports1);
        $chain1->supports();

        $notSupports = $this->createMock(OwnershipMetadataProviderInterface::class);
        $notSupports->expects($this->any())
            ->method('supports')
            ->willReturn(false);

        $supports2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $supports2->expects($this->once())
            ->method('supports');

        $chain2 = new ChainOwnershipMetadataProvider();
        $chain2->addProvider('alias1', $notSupports);
        $chain2->addProvider('alias2', $supports2);
        $chain2->supports();
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

        $this->assertIsArray($result);
        $this->assertEquals($metadataFromMockProvider2, $result);
    }

    public function testGetMetadataFromDefault()
    {
        $metadata = ['label' => 'testLabel1'];

        $default = $this->getMetadataProviderMock(true, $metadata);
        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);

        $result = $chain->getMetadata('stdClass');

        $this->assertIsArray($result);
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

    public function testGetUserClassWhenSupportedProviderNotFound()
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getUserClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getUserClass();
    }

    public function testGetBusinessUnitClassWhenSupportedProviderNotFound()
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getBusinessUnitClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getBusinessUnitClass();
    }

    public function testGetOrganizationClassWhenSupportedProviderNotFound()
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method('getOrganizationClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getOrganizationClass();
    }

    /**
     * @return OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMetadataProviderMock(bool $isSupports = true, array $metadata = [])
    {
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
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
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $provider->expects($this->once())
            ->method('getUserClass')
            ->willReturn(\stdClass::class);

        $this->assertEquals(\stdClass::class, $chain->getUserClass());
    }

    public function testEmulatedProvider()
    {
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $emulated = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);
        $chain->addProvider('emulated', $emulated);

        $chain->startProviderEmulation('emulated');

        $provider->expects($this->never())
            ->method('getUserClass');
        $emulated->expects($this->once())
            ->method('getUserClass')
            ->willReturn(\stdClass::class);
        $this->assertEquals(\stdClass::class, $chain->getUserClass());

        $chain->stopProviderEmulation();
    }

    public function testEmulationNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider with "alias" alias not registered');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->startProviderEmulation('alias');
    }

    public function testClearCache()
    {
        $provider1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $default = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects($this->once())
            ->method('clearCache');
        $provider1->expects($this->once())
            ->method('clearCache');
        $default->expects($this->once())
            ->method('clearCache');

        $chain->clearCache();
    }

    public function testWarmUpCache()
    {
        $provider1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $default = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects($this->once())
            ->method('warmUpCache');
        $provider1->expects($this->once())
            ->method('warmUpCache');
        $default->expects($this->once())
            ->method('warmUpCache');

        $chain->warmUpCache();
    }
}
