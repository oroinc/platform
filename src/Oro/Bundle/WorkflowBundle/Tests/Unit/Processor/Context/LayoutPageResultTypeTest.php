<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutResultTypeInterface;
use PHPUnit\Framework\TestCase;

class LayoutPageResultTypeTest extends TestCase
{
    public function testInterface(): void
    {
        $formRouteName = 'route_name';
        $resultType = new LayoutPageResultType($formRouteName);

        $this->assertInstanceOf(LayoutResultTypeInterface::class, $resultType);
        $this->assertEquals(LayoutPageResultType::NAME, $resultType->getName());
        $this->assertEquals('layout_page', $resultType->getName());
        $this->assertTrue($resultType->supportsCustomForm());
        $this->assertEquals($formRouteName, $resultType->getFormRouteName());
    }
}
