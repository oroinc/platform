<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider\Event;

use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;

class OnButtonsMatchedTest extends \PHPUnit\Framework\TestCase
{
    public function testGetButtons()
    {
        $collection = new ButtonsCollection();

        $event = new OnButtonsMatched($collection);

        $this->assertSame($collection, $event->getButtons());
    }
}
