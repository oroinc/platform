<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\WorkflowBundle\Acl\Extension\WorkflowLabel;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowLabelTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testTrans()
    {
        $label = new WorkflowLabel('test');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('test', [], 'workflows')
            ->willReturn('translated');

        self::assertEquals('translated', $label->trans($this->translator));
    }

    public function testSerialization()
    {
        $label = new WorkflowLabel('test');

        $unserialized = unserialize(serialize($label));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }

    public function testSetState()
    {
        $label = new WorkflowLabel('test');

        $unserialized = eval(sprintf('return %s;', var_export($label, true)));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }
}
