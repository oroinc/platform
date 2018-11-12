<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;

class ChainOwnerTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainOwnerTreeProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ChainOwnerTreeProvider();
    }

    public function testSupports()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);

        $notSupported = $this->getProviderMock();
        $notSupported->expects($this->never())->method('supports');

        $this->provider->addProvider($provider);
        $this->provider->addProvider($notSupported);

        $this->assertTrue($this->provider->supports());
    }

    public function testAddProvider()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);

        // guard
        $this->assertFalse($this->provider->supports());

        $this->provider->addProvider($provider);
        $this->assertTrue($this->provider->supports());
    }

    public function testSupportsDefault()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->never())->method('supports');

        $default = $this->getProviderMock();

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertTrue($this->provider->supports());
    }

    public function testGetTree()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);
        $provider->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        $default = $this->getProviderMock();

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf(OwnerTreeInterface::class, $this->provider->getTree());
    }

    public function testGetTreeDefault()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(false);
        $provider->expects($this->never())->method('getTree');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf(OwnerTreeInterface::class, $this->provider->getTree());
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
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('clear');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('clear');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->clear();
    }

    public function testWarmUpCache()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('warmUpCache');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('warmUpCache');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->warmUpCache();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OwnerTreeProviderInterface
     */
    protected function getProviderMock()
    {
        return $this->createMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
    }
}
