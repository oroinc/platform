<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class ChainMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithoutProviders()
    {
        $chain = new ChainMetadataProvider();

        $this->assertAttributeCount(0, 'providers', $chain);
    }

    public function testPassProviderThroughConstructor()
    {
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');
        $chain = new ChainMetadataProvider([$provider]);

        $this->assertAttributeCount(1, 'providers', $chain);
        $this->assertAttributeContains($provider, 'providers', $chain);
    }

    public function testAddProvider()
    {
        $provider1 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');
        $provider2 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $chain = new ChainMetadataProvider();
        $chain->addProvider($provider1);

        $this->assertAttributeCount(1, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);

        $chain->addProvider($provider2);

        $this->assertAttributeCount(2, 'providers', $chain);
        $this->assertAttributeContains($provider1, 'providers', $chain);
        $this->assertAttributeContains($provider2, 'providers', $chain);
    }

    public function testSupports()
    {
        $chain = new ChainMetadataProvider();
        $this->assertFalse($chain->supports());

        $chain->addProvider($this->getMetadataProviderMock(false));
        $this->assertFalse($chain->supports());

        $chain = new ChainMetadataProvider();
        $chain->addProvider($this->getMetadataProviderMock(false));
        $chain->addProvider($this->getMetadataProviderMock(true));
        $this->assertTrue($chain->supports());
    }

    public function testGetMetadata()
    {
        $metadataFromMockProvider1 = ['label' => 'testLabel1'];
        $metadataFromMockProvider2 = ['label' => 'testLabel2'];

        $chain = new ChainMetadataProvider();
        $chain->addProvider($this->getMetadataProviderMock(false, $metadataFromMockProvider1));
        $chain->addProvider($this->getMetadataProviderMock(true, $metadataFromMockProvider2));

        $result = $chain->getMetadata('stdClass');

        $this->assertInternalType('array', $result);
        $this->assertEquals($metadataFromMockProvider2, $result);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $levelClassMethod
     * @param bool $deep
     * @param string $levelClass
     */
    public function testGetLevelClass($levelClassMethod, $deep = false, $levelClass = 'stdClass')
    {
        $provider = $this->getMetadataProviderMock(true);

        if ($deep) {
            $provider->expects($this->once())
                ->method($levelClassMethod)
                ->with($deep)
                ->willReturn($levelClass);
        } else {
            $provider->expects($this->once())
                ->method($levelClassMethod)
                ->willReturn($levelClass);
        }

        $chain = new ChainMetadataProvider();
        $chain->addProvider($provider);

        $this->assertEquals($levelClass, $chain->$levelClassMethod($deep));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $levelClassMethod
     * @param bool $deep
     * @param string $levelClass
     *
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\NoSupportsMetadataProviderException
     * @expectedExceptionMessage Found no supports provider in chain
     */
    public function testGetLevelClassException($levelClassMethod, $deep = false, $levelClass = 'stdClass')
    {
        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method($levelClassMethod);

        $chain = new ChainMetadataProvider();
        $chain->addProvider($provider);

        $this->assertEquals($levelClass, $chain->$levelClassMethod($deep));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                'levelClassMethod' => 'getBasicLevelClass'
            ],
            [
                'levelClassMethod' => 'getLocalLevelClass'
            ],
            [
                'levelClassMethod' => 'getLocalLevelClass',
                'deep' => true,
                'levelClass' => 'stdClassDeep'
            ],
            [
                'levelClassMethod' => 'getGlobalLevelClass'
            ],
            [
                'levelClassMethod' => 'getSystemLevelClass'
            ]
        ];
    }

    /**
     * @param bool $isSupports
     * @param array $metadata
     * @return MetadataProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetadataProviderMock($isSupports = true, array $metadata = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');
        $provider->expects($this->once())
            ->method('supports')
            ->willReturn($isSupports);
        $provider->expects($isSupports && count($metadata) ? $this->once() : $this->never())
            ->method('getMetadata')
            ->with('stdClass')
            ->willReturn($metadata);

        return $provider;
    }
}
