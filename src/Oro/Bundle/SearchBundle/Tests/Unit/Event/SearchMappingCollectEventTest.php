<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use PHPUnit\Framework\TestCase;

class SearchMappingCollectEventTest extends TestCase
{
    public function testEvent(): void
    {
        $mapConfig = ['test'];
        $event = new SearchMappingCollectEvent($mapConfig);
        $this->assertSame($mapConfig, $event->getMappingConfig());
        $updatedMapConfig = array_merge($mapConfig, ['customEntity']);
        $event->setMappingConfig($updatedMapConfig);
        $this->assertSame($updatedMapConfig, $event->getMappingConfig());
    }
}
