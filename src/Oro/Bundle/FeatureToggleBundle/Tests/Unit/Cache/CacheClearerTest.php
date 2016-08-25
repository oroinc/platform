<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Cache;

use Oro\Bundle\FeatureToggleBundle\Cache\CacheClearer;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;

class CacheClearerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationProvider;

    /**
     * @var CacheClearer
     */
    protected $cacheClearer;

    protected function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheClearer = new CacheClearer($this->configurationProvider);
    }

    public function testClear()
    {
        $cacheDir = '.';

        $this->configurationProvider->expects($this->once())
            ->method('clearCache');
        $this->cacheClearer->clear($cacheDir);
    }
}
