<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationContextResolverTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;
    private TranslationContextResolver $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new TranslationContextResolver($this->translator);
    }

    public function testResolve(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.translation.context.ui_label')
            ->willReturn('UI Label');

        $this->assertEquals('UI Label', $this->extension->resolve('Translation Key'));
    }
}
