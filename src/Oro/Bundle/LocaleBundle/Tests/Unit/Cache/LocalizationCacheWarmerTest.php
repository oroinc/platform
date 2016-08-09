<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Cache;

use Oro\Bundle\LocaleBundle\Cache\LocalizationCacheWarmer;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

class LocalizationCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var LocalizationCacheWarmer */
    protected $warmer;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(LocalizationManager::class)->disableOriginalConstructor()->getMock();

        $this->warmer = new LocalizationCacheWarmer($this->manager);
    }

    protected function tearDown()
    {
        unset($this->warmer, $this->manager);
    }

    public function testClear()
    {
        $this->manager->expects($this->once())->method('warmUpCache');

        $this->warmer->warmUp(null);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
