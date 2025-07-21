<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider\Event;

use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use PHPUnit\Framework\TestCase;

class OnButtonsMatchedTest extends TestCase
{
    public function testGetButtons(): void
    {
        $collection = new ButtonsCollection();

        $event = new OnButtonsMatched($collection);

        $this->assertSame($collection, $event->getButtons());
    }
}
