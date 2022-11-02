<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Formatter;

use Oro\Bundle\TranslationBundle\Formatter\TranslatorFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TranslatorFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new TranslatorFormatter($this->translator);
    }

    public function testFormat(): void
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->assertEquals('translated_value', $this->formatter->format('translated_value'));
    }

    public function testGetDefaultValue(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('translated_value');

        $this->assertEquals('translated_value', $this->formatter->getDefaultValue());
    }
}
