<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Provider;

use Oro\Bundle\SoapBundle\Provider\ChainMetadataProvider;
use Oro\Bundle\SoapBundle\Tests\Unit\Provider\Stub\StubMetadataProvider;

class ChainMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithoutProviders()
    {
        $this->createChain();
    }

    public function testPassProviderThroughConstuctor()
    {
        $provider = $this->getMock('Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface');
        $chain    = $this->createChain([$provider]);
        $this->assertAttributeContains($provider, 'providers', $chain);
    }

    public function testPassProvidersThoughAdder()
    {
        $provider = $this->getMock('Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface');
        $chain    = $this->createChain();
        $chain->addProvider($provider);

        $this->assertAttributeContains($provider, 'providers', $chain);
    }

    public function testGetMetadata()
    {
        $metadataFromMockProvider = ['phpType' => '\stdClass'];
        $metadataFromStubProvider = ['label' => 'testLabel'];

        $object = new \stdClass();

        $provider = $this->getMock('Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface');
        $provider->expects($this->once())->method('getMetadataFor')->with($this->equalTo($object))
            ->willReturn($metadataFromMockProvider);
        $provider2 = new StubMetadataProvider($metadataFromStubProvider);

        $chain = $this->createChain([$provider]);
        $chain->addProvider($provider2);

        $result = $chain->getMetadataFor($object);
        $this->assertInternalType('array', $result);
        $this->assertEquals($metadataFromStubProvider + $metadataFromMockProvider, $result);
    }

    /**
     * @param array $providers
     *
     * @return ChainMetadataProvider
     */
    protected function createChain($providers = [])
    {
        return new ChainMetadataProvider($providers);
    }
}
