<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailOwnerConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EmailOwnerConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWhenNoProviders()
    {
        $container = new ContainerBuilder();
        $storageDef = $container->register('oro_email.email.owner.provider.storage');

        $compiler = new EmailOwnerConfigurationPass();
        $compiler->process($container);

        self::assertSame(
            [],
            $storageDef->getMethodCalls()
        );
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $storageDef = $container->register('oro_email.email.owner.provider.storage');

        $container->register('provider1')->addTag('oro_email.owner.provider', ['order' => 3]);
        $container->register('provider2')->addTag('oro_email.owner.provider', ['order' => 1]);
        $container->register('provider3')->addTag('oro_email.owner.provider');
        $container->register('provider4')->addTag('oro_email.owner.provider', ['order' => 2]);
        $container->register('provider5')->addTag('oro_email.owner.provider', ['order' => 4]);

        $compiler = new EmailOwnerConfigurationPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addProvider', [new Reference('provider2')]],
                ['addProvider', [new Reference('provider4')]],
                ['addProvider', [new Reference('provider1')]],
                ['addProvider', [new Reference('provider5')]],
                ['addProvider', [new Reference('provider3')]]
            ],
            $storageDef->getMethodCalls()
        );
    }
}
