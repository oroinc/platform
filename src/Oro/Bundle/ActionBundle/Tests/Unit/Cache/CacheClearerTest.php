<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Cache;

use Oro\Bundle\ActionBundle\Cache\CacheClearer;

class CacheClearerTest extends AbstractCacheServiceTest
{
    /** @var CacheClearer */
    protected $clearer;

    protected function setUp()
    {
        parent::setUp();
        $this->clearer = new CacheClearer($this->provider);
    }

    protected function tearDown()
    {
        unset($this->clearer);
        parent::tearDown();
    }

    public function testClear()
    {
        $this->provider->expects($this->once())
            ->method('clearCache');

        $this->clearer->clear(null);
    }
}
