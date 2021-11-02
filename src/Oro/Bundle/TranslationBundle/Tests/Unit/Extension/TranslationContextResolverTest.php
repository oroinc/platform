<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationContextResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TranslationContextResolver */
    private $extension;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new TranslationContextResolver($this->translator);
    }

    public function testResolve()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.translation.context.ui_label')
            ->willReturn('UI Label');

        $this->assertEquals('UI Label', $this->extension->resolve('Translation Key'));
    }
}
