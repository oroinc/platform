<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentBuilderCompilerPass;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DocumentBuilderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private DocumentBuilderCompilerPass $compiler;

    private ContainerBuilder $container;

    private Definition $factory;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new DocumentBuilderCompilerPass();

        $this->factory = $this->container->setDefinition(
            'oro_api.document_builder_factory',
            new Definition(DocumentBuilderFactory::class, [[]])
        );
    }

    public function testProcessWhenNoDocumentBuilders(): void
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->factory->getArgument('$documentBuilders'));

        $serviceLocatorReference = $this->factory->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcess(): void
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->setShared(false);
        $documentBuilder1->addTag(
            'oro.api.document_builder',
            ['requestType' => 'rest']
        );
        $documentBuilder2 = $this->container->setDefinition('document_builder2', new Definition());
        $documentBuilder2->setShared(false);
        $documentBuilder2->addTag(
            'oro.api.document_builder',
            ['priority' => -10]
        );
        $documentBuilder2->addTag(
            'oro.api.document_builder',
            ['requestType' => 'json_api', 'priority' => 10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['document_builder2', 'json_api'],
                ['document_builder1', 'rest'],
                ['document_builder2', null]
            ],
            $this->factory->getArgument('$documentBuilders')
        );

        $serviceLocatorReference = $this->factory->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'document_builder1' => new ServiceClosureArgument(new Reference('document_builder1')),
                'document_builder2' => new ServiceClosureArgument(new Reference('document_builder2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWhenDocumentBuilderIsShared(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The document builder service "document_builder1" should be non shared.');

        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->addTag(
            'oro.api.document_builder',
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);
    }
}
