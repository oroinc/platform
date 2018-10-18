<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\ChainDocumentationProvider;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentationProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                new Reference('provider3'),
                new Reference('provider1'),
                new Reference('provider2')
            ],
            $this->chainDocumentationProvider->getArgument(0)
        );
    }
}
