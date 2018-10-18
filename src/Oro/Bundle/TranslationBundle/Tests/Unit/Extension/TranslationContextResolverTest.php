<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Extension;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationContextResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var TranslationContextResolver */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new TranslationContextResolver($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->translator, $this->extension);
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
