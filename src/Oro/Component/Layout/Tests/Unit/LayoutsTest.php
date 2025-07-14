<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Layouts;
use PHPUnit\Framework\TestCase;

class LayoutsTest extends TestCase
{
    public function testCoreExtensionIsAdded(): void
    {
        $this->assertInstanceOf(
            BaseType::class,
            Layouts::createLayoutFactory()->getType(BaseType::NAME)
        );
    }
}
