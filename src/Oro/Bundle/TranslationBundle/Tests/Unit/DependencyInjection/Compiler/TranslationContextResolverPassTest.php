<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslationContextResolverPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('context_resolver1')
            ->addTag('oro_translation.extension.translation_context_resolver', []);
        $container->register('context_resolver2')
            ->addTag('oro_translation.extension.translation_context_resolver', ['priority' => -10]);
        $container->register('context_resolver3')
            ->addTag('oro_translation.extension.translation_context_resolver', ['priority' => 10]);

        $translationContext = $container->register('oro_translation.provider.translation_context');

        $compiler = new TranslationContextResolverPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addExtension', [new Reference('context_resolver2'), 'context_resolver2']],
                ['addExtension', [new Reference('context_resolver1'), 'context_resolver1']],
                ['addExtension', [new Reference('context_resolver3'), 'context_resolver3']]
            ],
            $translationContext->getMethodCalls()
        );
    }
}
