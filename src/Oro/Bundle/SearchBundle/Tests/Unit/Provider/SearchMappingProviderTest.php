<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingProviderTest extends AbstractSearchMappingProviderTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->getMock(Cache::class);

        $this->provider = new SearchMappingProvider($this->eventDispatcher, $this->cacheDriver);
        $this->provider->setMappingConfig($this->testMapping);
    }

    public function testGetMappingConfigCached()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('oro_search.mapping_config')
            ->willReturn(true);

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('oro_search.mapping_config')
            ->willReturn($this->testMapping);

        $this->assertEquals($this->testMapping, $this->provider->getMappingConfig());
    }

    public function testClearMappingCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('delete')
            ->with('oro_search.mapping_config');

        $this->provider->clearMappingCache();
    }
}
