<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Label;
use Symfony\Contracts\Translation\TranslatorInterface;

class LabelTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testName()
    {
        $label = new Label('test');
        self::assertEquals('test', $label->getName());

        $label->setName('test1');
        self::assertEquals('test1', $label->getName());

        self::assertEquals('Label: test1', (string)$label);
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

    public function testTransWhenNoTranslation()
    {
        $label = new Label('test');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('test', [], null)
            ->willReturn('test');

        self::assertSame('', $label->trans($this->translator));
    }

    public function testTransWithDomain()
    {
        $label = new Label('test', 'test_domain');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('test', [], 'test_domain')
            ->willReturn('translated');

        self::assertEquals('translated', $label->trans($this->translator));
    }
}
