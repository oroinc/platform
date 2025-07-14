<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowLabel;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowLabelTest extends TestCase
{
    public function testTrans(): void
    {
        $label = new WorkflowLabel('test');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('test', [], 'workflows')
            ->willReturn('translated');

        self::assertEquals('translated', $label->trans($translator));
    }

    public function testSerialization(): void
    {
        $label = new WorkflowLabel('test');

        $unserialized = unserialize(serialize($label));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }

    public function testSetState(): void
    {
        $label = new WorkflowLabel('test');

        $unserialized = eval(sprintf('return %s;', var_export($label, true)));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }
}
