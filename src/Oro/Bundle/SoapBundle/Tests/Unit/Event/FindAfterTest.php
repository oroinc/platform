<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Event;

use Oro\Bundle\SoapBundle\Event\FindAfter;
use PHPUnit\Framework\TestCase;

class FindAfterTest extends TestCase
{
    public function testGetEntity(): void
    {
        $testEntity = new \stdClass();
        $event = new FindAfter($testEntity);
        $this->assertSame($testEntity, $event->getEntity());
    }
}
