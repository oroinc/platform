<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;

class ContentProviderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $managerDefinition = new Definition('\Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager');
        $providerDefinition = new Definition('\FooBundle\FooProvider');
        $twigDefinition = new Definition('\Twig_Engine');
        $providerDefinition->addTag(ContentProviderPass::CONTENT_PROVIDER_TAG);

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setDefinition(ContentProviderPass::CONTENT_PROVIDER_MANAGER_SERVICE, $managerDefinition);
        $containerBuilder->setDefinition('testId', $providerDefinition);
        $containerBuilder->setDefinition(ContentProviderPass::TWIG_SERVICE_KEY, $twigDefinition);

        $pass = new ContentProviderPass();
        $pass->process($containerBuilder);

        $calls = $managerDefinition->getMethodCalls();
        $this->assertNotEmpty($calls);
        $this->assertEquals(array_pop($calls), ['addContentProvider', [new Reference('testId'), true]]);

        $calls = $twigDefinition->getMethodCalls();
        $this->assertNotEmpty($calls);
        $this->assertEquals(
            array_pop($calls),
            [
                'addGlobal',
                [
                    'oro_ui_content_provider_manager',
                    new Reference(ContentProviderPass::CONTENT_PROVIDER_MANAGER_SERVICE)
                ]
            ]
        );
    }
}
