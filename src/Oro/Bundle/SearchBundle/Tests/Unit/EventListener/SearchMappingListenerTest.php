<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\EventListener\SearchMappingListener;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingListenerTest extends \PHPUnit\Framework\TestCase
{
    private $searchMappingListener;

    /** @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $searchMappingProvider;

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
