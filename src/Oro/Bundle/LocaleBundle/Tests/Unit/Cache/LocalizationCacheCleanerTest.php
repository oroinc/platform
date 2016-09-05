<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Cache;

use Oro\Bundle\LocaleBundle\Cache\LocalizationCacheCleaner;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

class LocalizationCacheCleanerTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var LocalizationCacheCleaner */
    protected $cleaner;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(LocalizationManager::class)->disableOriginalConstructor()->getMock();

        $this->cleaner = new LocalizationCacheCleaner($this->manager);
    }

    protected function tearDown()
    {
        unset($this->cleaner, $this->manager);
    }

    public function testClear()
    {
        $this->manager->expects($this->once())->method('clearCache');

        $this->cleaner->clear(null);
    }
}
