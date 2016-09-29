<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver;

class TranslationContextResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslationContextResolver */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);

        $this->extension = new TranslationContextResolver($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
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
