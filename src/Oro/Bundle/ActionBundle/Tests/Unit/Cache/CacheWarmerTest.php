<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Cache;

use Oro\Bundle\ActionBundle\Cache\CacheWarmer;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;

class CacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CacheWarmer */
    protected $warmer;

    protected function setUp()
    {
        $this->warmer = new CacheWarmer();
    }

    protected function tearDown()
    {
        unset($this->warmer);
    }

    public function testClear()
    {
        $this->warmer->addProvider($this->getProviderMock());
        $this->warmer->addProvider($this->getProviderMock());

        $this->warmer->warmUp(null);
    }

    public function testIsOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigurationProviderInterface
     */
    protected function getProviderMock()
    {
        $provider = $this->getMock('Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface');
        $provider->expects($this->once())->method('warmUpCache');

        return $provider;
    }
}
