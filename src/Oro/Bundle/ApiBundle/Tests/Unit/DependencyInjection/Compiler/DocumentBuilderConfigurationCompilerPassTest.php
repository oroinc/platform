<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DocumentBuilderConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;

class DocumentBuilderConfigurationCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var DocumentBuilderConfigurationCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $factory;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new DocumentBuilderConfigurationCompilerPass();

        $this->factory = $this->container->setDefinition(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_FACTORY_SERVICE_ID,
            new Definition(DocumentBuilderFactory::class, [[]])
        );
    }

    public function testProcessWhenNoDataTransformers()
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->factory->getArgument(0));
    }

    public function testProcess()
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->setShared(false);
        $documentBuilder1->addTag(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_TAG,
            ['requestType' => 'rest']
        );
        $documentBuilder2 = $this->container->setDefinition('document_builder2', new Definition());
        $documentBuilder2->setShared(false);
        $documentBuilder2->addTag(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_TAG,
            ['priority' => -10]
        );
        $documentBuilder2->addTag(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_TAG,
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

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The document builder service "document_builder1" should be public and non shared.
     */
    public function testProcessWhenDocumentBuilderIsNotPublic()
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->setPublic(false);
        $documentBuilder1->addTag(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_TAG,
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The document builder service "document_builder1" should be public and non shared.
     */
    public function testProcessWhenDocumentBuilderIsShared()
    {
        $documentBuilder1 = $this->container->setDefinition('document_builder1', new Definition());
        $documentBuilder1->addTag(
            DocumentBuilderConfigurationCompilerPass::DOCUMENT_BUILDER_TAG,
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);
    }
}
