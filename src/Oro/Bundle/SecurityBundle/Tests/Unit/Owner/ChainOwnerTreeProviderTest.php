<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;

class ChainOwnerTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainOwnerTreeProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ChainOwnerTreeProvider();
    }

    public function testSupports()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('supports')->willReturn(true);

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $notSupported */
        $notSupported = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $notSupported->expects($this->never())->method('supports');

        $this->provider->addProvider($provider);
        $this->provider->addProvider($notSupported);

        $this->assertTrue($this->provider->supports());
    }

    public function testDoNotAddTwice()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('supports')->willReturn(false);

        $this->provider->addProvider($provider);
        $this->provider->addProvider($provider);

        $this->assertFalse($this->provider->supports());
    }

    public function testSupportsDefault()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->never())->method('supports');

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertTrue($this->provider->supports());
    }

    public function testGetTree()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('supports')->willReturn(true);
        $provider->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Owner\OwnerTree', $this->provider->getTree());
    }

    public function testGetTreeDefault()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('supports')->willReturn(false);
        $provider->expects($this->never())->method('getTree');

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $default->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Owner\OwnerTree', $this->provider->getTree());
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetTreeFailed()
    {
        $this->provider->getTree();
    }

    public function testClear()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('clear');

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $default->expects($this->once())->method('clear');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->clear();
    }

    public function testWarmUpCache()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $provider */
        $provider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $provider->expects($this->once())->method('warmUpCache');

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProviderInterface $default */
        $default = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
        $default->expects($this->once())->method('warmUpCache');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->warmUpCache();
    }
}
