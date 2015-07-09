<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class SearchMappingCollectEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $mapConfig = ['test'];
        $event = new SearchMappingCollectEvent($mapConfig);
        $this->assertSame($mapConfig, $event->getMappingConfig());
        $updatedMapConfig = array_merge($mapConfig, ['customEntity']);
        $event->setMappingConfig($updatedMapConfig);
        $this->assertSame($updatedMapConfig, $event->getMappingConfig());
    }
}
