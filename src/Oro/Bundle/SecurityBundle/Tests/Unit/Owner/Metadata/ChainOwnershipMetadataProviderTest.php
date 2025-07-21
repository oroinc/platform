<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChainOwnershipMetadataProviderTest extends TestCase
{
    private function getMetadataProviderMock(
        bool $supported,
        ?OwnershipMetadataInterface $metadata = null
    ): OwnershipMetadataProviderInterface&MockObject {
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider->expects(self::any())
            ->method('supports')
            ->willReturn($supported);
        if ($supported && null !== $metadata) {
            $provider->expects(self::once())
                ->method('getMetadata')
                ->with(\stdClass::class)
                ->willReturn($metadata);
        } else {
            $provider->expects(self::never())
                ->method('getMetadata');
        }

        return $provider;
    }

    public function testAddProvider(): void
    {
        $supports1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $supports1->expects(self::once())
            ->method('supports');

        $chain1 = new ChainOwnershipMetadataProvider();
        $chain1->addProvider('alias1', $supports1);
        $chain1->supports();

        $notSupports = $this->createMock(OwnershipMetadataProviderInterface::class);
        $notSupports->expects(self::any())
            ->method('supports')
            ->willReturn(false);

        $supports2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $supports2->expects(self::once())
            ->method('supports');

        $chain2 = new ChainOwnershipMetadataProvider();
        $chain2->addProvider('alias1', $notSupports);
        $chain2->addProvider('alias2', $supports2);
        $chain2->supports();
    }

    public function testSupports(): void
    {
        $chain = new ChainOwnershipMetadataProvider();
        self::assertFalse($chain->supports());

        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        self::assertFalse($chain->supports());

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        $chain->addProvider('alias2', $this->getMetadataProviderMock(true));
        self::assertTrue($chain->supports());
    }

    public function testSupportsWithDefault(): void
    {
        $chain = new ChainOwnershipMetadataProvider();
        self::assertFalse($chain->supports());

        $default = $this->getMetadataProviderMock(false);
        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        self::assertTrue($chain->supports());
    }

    public function testGetMetadata(): void
    {
        $metadataFromMockProvider1 = $this->createMock(OwnershipMetadataInterface::class);
        $metadataFromMockProvider2 = $this->createMock(OwnershipMetadataInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias1', $this->getMetadataProviderMock(false, $metadataFromMockProvider1));
        $chain->addProvider('alias2', $this->getMetadataProviderMock(true, $metadataFromMockProvider2));

        $result = $chain->getMetadata('stdClass');

        self::assertSame($metadataFromMockProvider2, $result);
    }

    public function testGetMetadataFromDefault(): void
    {
        $metadata = $this->createMock(OwnershipMetadataInterface::class);

        $default = $this->getMetadataProviderMock(true, $metadata);
        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);

        $result = $chain->getMetadata('stdClass');

        self::assertSame($metadata, $result);
    }

    public function testGetUserClass(): void
    {
        $userClass = 'Test\User';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects(self::once())
            ->method('getUserClass')
            ->willReturn($userClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        self::assertEquals($userClass, $chain->getUserClass());
    }

    public function testGetBusinessUnitClass(): void
    {
        $businessUnitClass = 'Test\BusinessUnit';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects(self::once())
            ->method('getBusinessUnitClass')
            ->willReturn($businessUnitClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        self::assertEquals($businessUnitClass, $chain->getBusinessUnitClass());
    }

    public function testGetOrganizationClass(): void
    {
        $organizationClass = 'Test\Organization';
        $provider = $this->getMetadataProviderMock(true);

        $provider->expects(self::once())
            ->method('getOrganizationClass')
            ->willReturn($organizationClass);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        self::assertEquals($organizationClass, $chain->getOrganizationClass());
    }

    public function testGetUserClassWhenSupportedProviderNotFound(): void
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects(self::never())
            ->method('getUserClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getUserClass();
    }

    public function testGetBusinessUnitClassWhenSupportedProviderNotFound(): void
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects(self::never())
            ->method('getBusinessUnitClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getBusinessUnitClass();
    }

    public function testGetOrganizationClassWhenSupportedProviderNotFound(): void
    {
        $this->expectException(UnsupportedMetadataProviderException::class);
        $this->expectExceptionMessage('Supported provider not found in chain');

        $provider = $this->getMetadataProviderMock(false);
        $provider->expects(self::never())
            ->method('getOrganizationClass');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $chain->getOrganizationClass();
    }

    public function testGetMaxAccessLevel(): void
    {
        $accessLevel = AccessLevel::SYSTEM_LEVEL;
        $object = \stdClass::class;

        $provider = $this->getMetadataProviderMock(true);
        $provider->expects(self::once())
            ->method('getMaxAccessLevel')
            ->with($accessLevel, $object)
            ->willReturn($accessLevel);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        self::assertEquals($accessLevel, $chain->getMaxAccessLevel($accessLevel, $object));
    }

    public function testSupportedProvider(): void
    {
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider->expects(self::any())
            ->method('supports')
            ->willReturn(true);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);

        $provider->expects(self::once())
            ->method('getUserClass')
            ->willReturn(\stdClass::class);

        self::assertEquals(\stdClass::class, $chain->getUserClass());
    }

    public function testEmulatedProvider(): void
    {
        $provider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider->expects(self::any())
            ->method('supports')
            ->willReturn(true);

        $emulated = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->addProvider('alias', $provider);
        $chain->addProvider('emulated', $emulated);

        $chain->startProviderEmulation('emulated');

        $provider->expects(self::never())
            ->method('getUserClass');
        $emulated->expects(self::once())
            ->method('getUserClass')
            ->willReturn(\stdClass::class);
        self::assertEquals(\stdClass::class, $chain->getUserClass());

        $chain->stopProviderEmulation();
    }

    public function testEmulationNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider with "alias" alias not registered');

        $chain = new ChainOwnershipMetadataProvider();
        $chain->startProviderEmulation('alias');
    }

    public function testClearCache(): void
    {
        $provider1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $default = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects(self::once())
            ->method('clearCache');
        $provider1->expects(self::once())
            ->method('clearCache');
        $default->expects(self::once())
            ->method('clearCache');

        $chain->clearCache();
    }

    public function testWarmUpCache(): void
    {
        $provider1 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $provider2 = $this->createMock(OwnershipMetadataProviderInterface::class);
        $default = $this->createMock(OwnershipMetadataProviderInterface::class);

        $chain = new ChainOwnershipMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects(self::once())
            ->method('warmUpCache');
        $provider1->expects(self::once())
            ->method('warmUpCache');
        $default->expects(self::once())
            ->method('warmUpCache');

        $chain->warmUpCache();
    }
}
