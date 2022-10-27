<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Provider;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Provider\ChainMetadataProvider;
use Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface;

class ChainMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructionWithoutProviders()
    {
        $chain = new ChainMetadataProvider([]);

        $this->assertEquals([], $chain->getMetadataFor($this->createMock(FormAwareInterface::class)));
    }

    public function testPassProviderThroughConstructor()
    {
        $object = $this->createMock(FormAwareInterface::class);

        $provider = $this->createMock(MetadataProviderInterface::class);
        $provider->expects($this->once())
            ->method('getMetadataFor')
            ->with($object)
            ->willReturn(['something']);

        $chain = new ChainMetadataProvider([$provider]);

        $this->assertEquals(['something'], $chain->getMetadataFor($object));
    }

    public function testPassProvidersThoughAdder()
    {
        $object = $this->createMock(FormAwareInterface::class);
        $provider = $this->createMock(MetadataProviderInterface::class);
        $provider->expects($this->once())
            ->method('getMetadataFor')
            ->with($object)
            ->willReturn(['something']);

        $chain = new ChainMetadataProvider();
        $chain->addProvider($provider);

        $this->assertEquals(['something'], $chain->getMetadataFor($object));
    }

    public function testGetMetadata()
    {
        $metadata1 = ['phpType' => 'something'];
        $metadata2 = ['label' => 'testLabel'];

        $object = $this->createMock(FormAwareInterface::class);

        $provider1 = $this->createMock(MetadataProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getMetadataFor')
            ->with($object)
            ->willReturn($metadata1);

        $provider2 = $this->createMock(MetadataProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getMetadataFor')
            ->with($object)
            ->willReturn($metadata2);

        $chain = new ChainMetadataProvider([$provider1]);
        $chain->addProvider($provider2);
        $result = $chain->getMetadataFor($object);

        $this->assertIsArray($result);
        $this->assertEquals(array_merge($metadata2, $metadata1), $result);
    }
}
