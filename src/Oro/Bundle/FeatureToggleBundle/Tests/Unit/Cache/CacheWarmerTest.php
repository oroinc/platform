<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Cache;

use Oro\Bundle\FeatureToggleBundle\Cache\CacheWarmer;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;

class CacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configurationProvider;

    /**
     * @var CacheWarmer
     */
    protected $cacheWarmer;

    protected function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheWarmer = new CacheWarmer($this->configurationProvider);
    }

    public function testWarmUp()
    {
        $cacheDir = '.';
        $this->configurationProvider->expects($this->once())
            ->method('warmUpCache');
        $this->cacheWarmer->warmUp($cacheDir);
    }

    public function testIsOptional()
    {
        $this->assertFalse($this->cacheWarmer->isOptional());
    }
}
