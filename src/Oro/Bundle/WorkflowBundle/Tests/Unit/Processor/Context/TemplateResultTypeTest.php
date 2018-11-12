<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context;

use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;

class TemplateResultTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $resultType = new TemplateResultType();

        $this->assertEquals('template_response', TemplateResultType::NAME);
        $this->assertInstanceOf(TransitActionResultTypeInterface::class, $resultType);
        $this->assertEquals(TemplateResultType::NAME, $resultType->getName());
        $this->assertTrue($resultType->supportsCustomForm());
    }
}
