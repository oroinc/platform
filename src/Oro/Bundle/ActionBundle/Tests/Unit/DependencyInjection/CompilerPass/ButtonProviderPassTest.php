<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ButtonProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ButtonProviderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('provider1')
            ->addTag('oro.action.extension.button_provider', []);
        $container->register('provider2')
            ->addTag('oro.action.extension.button_provider', ['priority' => -10]);
        $container->register('provider3')
            ->addTag('oro.action.extension.button_provider', ['priority' => 10]);

        $chainProvider = $container->register('oro_action.provider.button');

        $compiler = new ButtonProviderPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addExtension', [new Reference('provider2'), 'provider2']],
                ['addExtension', [new Reference('provider1'), 'provider1']],
                ['addExtension', [new Reference('provider3'), 'provider3']]
            ],
            $chainProvider->getMethodCalls()
        );
    }
}
