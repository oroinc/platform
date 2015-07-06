<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class ChainMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithoutProviders()
    {
        $chain = new ChainMetadataProvider();

        $this->assertAttributeCount(0, 'providers', $chain);
    }

    public function testAddProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider1 */
        $provider1 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider2 */
        $provider2 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $chain = new ChainMetadataProvider();
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
        $chain = new ChainMetadataProvider();
        $this->assertFalse($chain->supports());

        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        $this->assertFalse($chain->supports());

        $chain = new ChainMetadataProvider();
        $chain->addProvider('alias1', $this->getMetadataProviderMock(false));
        $chain->addProvider('alias2', $this->getMetadataProviderMock(true));
        $this->assertTrue($chain->supports());
    }

    public function testSupportsWithDefault()
    {
        $chain = new ChainMetadataProvider();
        $this->assertFalse($chain->supports());

        $default = $this->getMetadataProviderMock(false);
        $chain = new ChainMetadataProvider();
        $chain->setDefaultProvider($default);
        $this->assertTrue($chain->supports());
    }

    public function testGetMetadata()
    {
        $metadataFromMockProvider1 = ['label' => 'testLabel1'];
        $metadataFromMockProvider2 = ['label' => 'testLabel2'];

        $chain = new ChainMetadataProvider();
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
        $chain = new ChainMetadataProvider();
        $chain->setDefaultProvider($default);

        $result = $chain->getMetadata('stdClass');

        $this->assertInternalType('array', $result);
        $this->assertEquals($metadata, $result);
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
        $chain->addProvider('alias', $provider);

        $this->assertEquals($levelClass, $chain->$levelClassMethod($deep));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $levelClassMethod
     * @param bool $deep
     * @param string $levelClass
     *
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedMetadataProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetLevelClassException($levelClassMethod, $deep = false, $levelClass = 'stdClass')
    {
        $provider = $this->getMetadataProviderMock(false);
        $provider->expects($this->never())
            ->method($levelClassMethod);

        $chain = new ChainMetadataProvider();
        $chain->addProvider('alias', $provider);

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
        $object = 'SomeClass';

        $provider = $this->getMetadataProviderMock(true);
        $provider->expects($this->once())
            ->method('getMaxAccessLevel')
            ->with($accessLevel, $object)
            ->willReturn($accessLevel);

        $chain = new ChainMetadataProvider();
        $chain->addProvider('alias', $provider);

        $this->assertEquals($accessLevel, $chain->getMaxAccessLevel($accessLevel, $object));
    }

    public function testSupportedProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');
        $provider->expects($this->any())->method('supports')->willReturn(true);

        $chain = new ChainMetadataProvider();
        $chain->addProvider('alias', $provider);

        $provider->expects($this->once())->method('getBasicLevelClass')->willReturn('\stdClass');
        $provider->expects($this->once())->method('getSystemLevelClass')->willReturn('\stdClass');

        $this->assertEquals('\stdClass', $chain->getBasicLevelClass());
        $this->assertEquals('\stdClass', $chain->getSystemLevelClass());
    }

    public function testEmulatedProvider()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');
        $provider->expects($this->any())->method('supports')->willReturn(true);

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $emulated */
        $emulated = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $chain = new ChainMetadataProvider();
        $chain->addProvider('alias', $provider);
        $chain->addProvider('emulated', $emulated);

        $chain->startProviderEmulation('emulated');

        $provider->expects($this->never())->method('getBasicLevelClass');
        $emulated->expects($this->once())->method('getBasicLevelClass')->willReturn('\stdClass');
        $this->assertEquals('\stdClass', $chain->getBasicLevelClass());

        $chain->stopProviderEmulation();

        $emulated->expects($this->never())->method('getSystemLevelClass');
        $provider->expects($this->once())->method('getSystemLevelClass')->willReturn('\stdClass');
        $this->assertEquals('\stdClass', $chain->getSystemLevelClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Provider with "alias" alias not registered
     */
    public function testEmulationNotSupported()
    {
        $chain = new ChainMetadataProvider();
        $chain->startProviderEmulation('alias');
    }

    public function testClearCache()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider1 */
        $provider1 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider2 */
        $provider2 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $chain = new ChainMetadataProvider();
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider1 */
        $provider1 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $provider2 */
        $provider2 = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $chain = new ChainMetadataProvider();
        $chain->setDefaultProvider($default);
        $chain->addProvider('alias1', $provider1);
        $chain->addProvider('alias2', $provider2);

        $provider1->expects($this->once())->method('warmUpCache');
        $provider1->expects($this->once())->method('warmUpCache');
        $default->expects($this->once())->method('warmUpCache');

        $chain->warmUpCache();
    }
}
