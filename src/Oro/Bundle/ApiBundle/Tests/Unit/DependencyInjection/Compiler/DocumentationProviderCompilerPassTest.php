<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\ChainDocumentationProvider;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentationProviderCompilerPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DocumentationProviderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DocumentationProviderCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $chainDocumentationProvider;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new DocumentationProviderCompilerPass();

        $this->chainDocumentationProvider = $this->container->setDefinition(
            'oro_api.api_doc.documentation_provider',
            new Definition(ChainDocumentationProvider::class, [[]])
        );
    }

    public function testProcessWhenNoDocumentationProviders()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->chainDocumentationProvider->getArgument(0));

        $serviceLocatorReference = $this->chainDocumentationProvider->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess()
    {
        $provider1 = $this->container->setDefinition('provider1', new Definition());
        $provider1->addTag(
            'oro.api.documentation_provider'
        );
        $provider2 = $this->container->setDefinition('provider2', new Definition());
        $provider2->addTag(
            'oro.api.documentation_provider',
            ['priority' => -10]
        );
        $provider3 = $this->container->setDefinition('provider3', new Definition());
        $provider3->addTag(
            'oro.api.documentation_provider',
            ['priority' => 10]
        );
        $provider4 = $this->container->setDefinition('provider4', new Definition());
        $provider4->addTag(
            'oro.api.documentation_provider',
            ['requestType' => 'rest', 'priority' => -20]
        );
        $provider5 = $this->container->setDefinition('provider5', new Definition());
        $provider5->addTag(
            'oro.api.documentation_provider',
            ['requestType' => 'json_api', 'priority' => 20]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['provider5', 'json_api'],
                ['provider3', null],
                ['provider1', null],
                ['provider2', null],
                ['provider4', 'rest']
            ],
            $this->chainDocumentationProvider->getArgument(0)
        );

        $serviceLocatorReference = $this->chainDocumentationProvider->getArgument(1);
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'provider5' => new ServiceClosureArgument(new Reference('provider5')),
                'provider3' => new ServiceClosureArgument(new Reference('provider3')),
                'provider1' => new ServiceClosureArgument(new Reference('provider1')),
                'provider2' => new ServiceClosureArgument(new Reference('provider2')),
                'provider4' => new ServiceClosureArgument(new Reference('provider4'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
