<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\BeforeMapObjectEvent;

class BeforeMapObjectEventTest extends \PHPUnit\Framework\TestCase
{
    public function testMappingConfig()
    {
        $config = ['test'];
        $event  = new BeforeMapObjectEvent($config, new \stdClass());
        $this->assertSame($config, $event->getMappingConfig());

        $updatedConfig = ['test', 'custom'];
        $event->setMappingConfig($updatedConfig);
        $this->assertSame($updatedConfig, $event->getMappingConfig());
    }

    public function testEntity()
    {
        $entity = new \stdClass();
        $event  = new BeforeMapObjectEvent([], $entity);
        $this->assertSame($entity, $event->getEntity());
    }
}
