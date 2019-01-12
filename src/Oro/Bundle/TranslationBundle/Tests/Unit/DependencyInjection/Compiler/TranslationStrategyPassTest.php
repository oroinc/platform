<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslationStrategyPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('strategy1')
            ->addTag('oro_translation.extension.translation_strategy', []);
        $container->register('strategy2')
            ->addTag('oro_translation.extension.translation_strategy', ['priority' => -10]);
        $container->register('strategy3')
            ->addTag('oro_translation.extension.translation_strategy', ['priority' => 10]);

        $strategyProvider = $container->register('oro_translation.strategy.provider');

        $compiler = new TranslationStrategyPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addStrategy', [new Reference('strategy2'), 'strategy2']],
                ['addStrategy', [new Reference('strategy1'), 'strategy1']],
                ['addStrategy', [new Reference('strategy3'), 'strategy3']]
            ],
            $strategyProvider->getMethodCalls()
        );
    }
}
