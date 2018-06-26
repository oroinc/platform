<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context;

use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutResultTypeInterface;

class LayoutDialogResultTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $formRouteName = 'route_name';
        $resultType = new LayoutDialogResultType($formRouteName);

        $this->assertInstanceOf(LayoutResultTypeInterface::class, $resultType);
        $this->assertEquals(LayoutDialogResultType::NAME, $resultType->getName());
        $this->assertEquals('layout_dialog', $resultType->getName());
        $this->assertTrue($resultType->supportsCustomForm());
        $this->assertEquals($formRouteName, $resultType->getFormRouteName());
    }
}
