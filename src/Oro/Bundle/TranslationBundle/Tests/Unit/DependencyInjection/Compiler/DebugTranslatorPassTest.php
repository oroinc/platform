<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as SymfonyTranslator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class DebugTranslatorPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DebugTranslatorPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DebugTranslatorPass();
    }

    public function testProcessWhenDebugTranslatorDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_translation.debug_translator', false);
        $translatorDef = $container->register('translator.default', Translator::class);

        $this->compiler->process($container);

        self::assertEquals(Translator::class, $translatorDef->getClass());
    }

    public function testProcessWhenDebugTranslatorEnabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_translation.debug_translator', true);
        $translatorDef = $container->register('translator.default', Translator::class);

        $this->compiler->process($container);

        self::assertEquals(DebugTranslator::class, $translatorDef->getClass());
    }

    public function testProcessWhenDebugTranslatorEnabledButUnexpectedTranslatorClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The class for the "translator.default" service must be "%s", given "%s".',
            Translator::class,
            SymfonyTranslator::class
        ));

        $container = new ContainerBuilder();
        $container->setParameter('oro_translation.debug_translator', true);
        $container->register('translator.default', SymfonyTranslator::class);

        $this->compiler->process($container);
    }
}
