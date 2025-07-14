<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class EntityStructureOptionsEventTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(new EntityStructureOptionsEvent(), [
            ['data', [new EntityStructure()], []],
        ]);
    }
}
