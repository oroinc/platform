<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata\Stub\StubMetadataProvider;

class AbstractMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var StubMetadataProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['delete', 'deleteAll'])
            ->getMockForAbstractClass();

        $this->provider = new StubMetadataProvider([], $this->configProvider, null, $this->cache);
    }

    protected function tearDown()
    {
        unset($this->configProvider, $this->cache, $this->provider);
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('SomeClass');

        $this->provider->clearCache('SomeClass');
    }

    public function testClearCacheAll()
    {
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->provider->clearCache();
    }
}
