<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Cache;

use Oro\Bundle\ActionBundle\Cache\CacheWarmer;

class CacheWarmerTest extends AbstractCacheServiceTest
{
    /** @var CacheWarmer */
    protected $warmer;

    protected function setUp()
    {
        parent::setUp();
        $this->warmer = new CacheWarmer($this->provider);
    }

    protected function tearDown()
    {
        unset($this->warmer);
        parent::tearDown();
    }

    public function testClear()
    {
        $this->provider->expects($this->once())
            ->method('warmUpCache');

        $this->warmer->warmUp(null);
    }

    public function testIsOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }
}
