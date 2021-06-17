<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DebugTranslatorPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DebugTranslatorPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DebugTranslatorPass();
    }

    public function testProcessWhenDebugTranslatorDisabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_translation.debug_translator', false);
        $container->setParameter('translator.class', 'Translator');

        $this->compiler->process($container);

        self::assertEquals('Translator', $container->getParameter('translator.class'));
    }

    public function testProcessWhenDebugTranslatorEnabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_translation.debug_translator', true);
        $container->setParameter('translator.class', 'Translator');

        $this->compiler->process($container);

        self::assertEquals(DebugTranslator::class, $container->getParameter('translator.class'));
    }
}
