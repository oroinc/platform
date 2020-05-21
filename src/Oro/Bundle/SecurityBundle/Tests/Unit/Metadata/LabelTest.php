<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\Label;
use Symfony\Contracts\Translation\TranslatorInterface;

class LabelTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testTrans()
    {
        $label = new Label('test');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('test', [], null)
            ->willReturn('translated');

        self::assertEquals('translated', $label->trans($this->translator));
    }

    public function testSerialization()
    {
        $label = new Label('test');

        $unserialized = unserialize(serialize($label));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }

    public function testSetState()
    {
        $label = new Label('test');

        $unserialized = eval(sprintf('return %s;', var_export($label, true)));
        $this->assertEquals($label, $unserialized);
        $this->assertNotSame($label, $unserialized);
    }
}
