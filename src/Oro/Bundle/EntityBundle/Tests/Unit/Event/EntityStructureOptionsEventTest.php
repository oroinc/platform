<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityStructureOptionsEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(new EntityStructureOptionsEvent(), [
            ['data', [new EntityStructure()], []],
        ]);
    }
}
