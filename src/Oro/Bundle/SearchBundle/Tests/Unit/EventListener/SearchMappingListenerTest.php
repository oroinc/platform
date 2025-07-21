<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\EventListener\SearchMappingListener;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchMappingListenerTest extends TestCase
{
    private $searchMappingListener;

    private SearchMappingProvider&MockObject $searchMappingProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->searchMappingListener = new SearchMappingListener($this->searchMappingProvider);
    }

    public function testInvalidateCache(): void
    {
        $this->searchMappingProvider->expects($this->once())
            ->method('warmUpCache');

        $this->searchMappingListener->invalidateCache();
    }
}
