<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationAdaptersCollection;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationAdaptersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslationAdapterPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('service1')
            ->addTag('translation_adapter', []);

        $container->register('service2')
            ->addTag('translation_adapter', []);


        $translationAdapterPass = $container->register(TranslationAdaptersCollection::class);

        $compiler = new TranslationAdaptersPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addAdapter', [new Reference('service1'), 'service1']],
                ['addAdapter', [new Reference('service2'), 'service2']],
            ],
            $translationAdapterPass->getMethodCalls()
        );
    }
}
