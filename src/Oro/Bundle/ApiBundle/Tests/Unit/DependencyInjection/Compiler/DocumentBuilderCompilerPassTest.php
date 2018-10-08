<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentBuilderCompilerPass;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DocumentBuilderCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DocumentBuilderCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $factory;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new DocumentBuilderCompilerPass();

        $this->factory = $this->container->setDefinition(
            'oro_api.document_builder_factory',
            new Definition(DocumentBuilderFactory::class, [[]])
        );
    }

    public function testProcessWhenNoDocumentBuilders()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->factory->getArgument(0));
    }

    public function testProcess()
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
            $this->factory->getArgument(0)
        );
    }

    public function testProcessWhenDocumentBuilderIsNotPublic()
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->setShared(false);
        $documentBuilder1->setPublic(false);
        $documentBuilder1->addTag(
            'oro.api.document_builder',
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['document_builder1', 'rest']
            ],
            $this->factory->getArgument(0)
        );
        self::assertTrue($documentBuilder1->isPublic());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The document builder service "document_builder1" should be non shared.
     */
    public function testProcessWhenDocumentBuilderIsShared()
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->addTag(
            'oro.api.document_builder',
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);
    }
}
