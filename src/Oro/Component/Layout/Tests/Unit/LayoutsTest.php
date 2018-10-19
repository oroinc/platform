<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Layouts;

class LayoutsTest extends \PHPUnit\Framework\TestCase
{
    public function testCoreExtensionIsAdded()
    {
        $this->assertInstanceOf(
            'Oro\Component\Layout\Block\Type\BaseType',
            Layouts::createLayoutFactory()->getType(BaseType::NAME)
        );
    }
}
