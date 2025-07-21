<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler\PdfDocumentOperatorRegistryPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class PdfDocumentOperatorRegistryPassTest extends TestCase
{
    private PdfDocumentOperatorRegistryPass $compilerPass;

    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->compilerPass = new PdfDocumentOperatorRegistryPass();
    }

    public function testProcessWithValidTaggedServicesPopulatesRegistry(): void
    {
        // Define the registry service
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        $registryDefinition = new Definition();
        $this->containerBuilder->setDefinition($registryServiceId, $registryDefinition);

        // Add tagged services
        $operatorId1 = 'acme.operator.sample_1';
        $operatorId2 = 'acme.operator.sample_2';
        $this->containerBuilder->setDefinition($operatorId1, new Definition())
            ->addTag(
                'oro_pdf_generator.pdf_document.operator',
                ['entity_class' => 'EntityClass1', 'mode' => 'instant']
            );
        $this->containerBuilder->setDefinition($operatorId2, new Definition())
            ->addTag(
                'oro_pdf_generator.pdf_document.operator',
                ['entity_class' => 'EntityClass2', 'mode' => 'deferred']
            );

        // Run the compiler pass
        $this->compilerPass->process($this->containerBuilder);

        // Retrieve the updated registry definition
        $updatedRegistryDefinition = $this->containerBuilder->getDefinition($registryServiceId);

        // Assertions
        self::assertTrue($this->containerBuilder->hasDefinition($registryServiceId));

        $arguments = $updatedRegistryDefinition->getArguments();
        self::assertArrayHasKey('$pdfDocumentOperatorLocator', $arguments);

        $serviceLocatorArgument = $arguments['$pdfDocumentOperatorLocator'];
        $serviceLocatorDefinitions = $serviceLocatorArgument->getValues();

        // Assertions for entity classes
        self::assertArrayHasKey('EntityClass1', $serviceLocatorDefinitions);
        self::assertArrayHasKey('EntityClass2', $serviceLocatorDefinitions);

        // Assertions for mode locators
        $entityClass1Locator = $serviceLocatorDefinitions['EntityClass1'];
        $entityClass2Locator = $serviceLocatorDefinitions['EntityClass2'];

        self::assertInstanceOf(Definition::class, $entityClass1Locator);
        self::assertInstanceOf(Definition::class, $entityClass2Locator);

        $entityClass1Modes = $entityClass1Locator->getArgument(0);
        $entityClass2Modes = $entityClass2Locator->getArgument(0);

        self::assertArrayHasKey('instant', $entityClass1Modes);
        self::assertArrayHasKey('deferred', $entityClass2Modes);

        self::assertInstanceOf(ServiceClosureArgument::class, $entityClass1Modes['instant']);
        self::assertInstanceOf(ServiceClosureArgument::class, $entityClass2Modes['deferred']);
    }

    public function testProcessWithoutRegistryServiceDoesNothing(): void
    {
        // Ensure the registry service is not defined
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        self::assertFalse($this->containerBuilder->hasDefinition($registryServiceId));

        self::assertCount(1, $this->containerBuilder->getDefinitions());

        // Run the compiler pass
        $this->compilerPass->process($this->containerBuilder);

        // Assertions: The container should remain unchanged
        self::assertFalse($this->containerBuilder->hasDefinition($registryServiceId));
        self::assertCount(1, $this->containerBuilder->getDefinitions());
    }

    public function testProcessWithMissingModeThrowsException(): void
    {
        // Define the registry service
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        $registryDefinition = new Definition();
        $this->containerBuilder->setDefinition($registryServiceId, $registryDefinition);

        // Add a tagged service with missing attributes
        $invalidServiceId = 'acme.operator.invalid';
        $this->containerBuilder
            ->setDefinition($invalidServiceId, new Definition())
            ->addTag('oro_pdf_generator.pdf_document.operator', ['entity_class' => 'EntityClass1']); // Missing 'mode'

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The service "%s" must define "entity_class" and "mode" attributes in its tag.',
                $invalidServiceId
            )
        );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithMissingEntityClassThrowsException(): void
    {
        // Define the registry service
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        $registryDefinition = new Definition();
        $this->containerBuilder->setDefinition($registryServiceId, $registryDefinition);

        // Add a tagged service with missing attributes
        $invalidServiceId = 'acme.operator.invalid';
        $this->containerBuilder->setDefinition($invalidServiceId, new Definition())
            ->addTag('oro_pdf_generator.pdf_document.operator', ['mode' => 'instant']); // Missing 'entity_class'

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The service "%s" must define "entity_class" and "mode" attributes in its tag.',
                $invalidServiceId
            )
        );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithNoTaggedServicesLeavesRegistryEmpty(): void
    {
        // Define the registry service
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        $registryDefinition = new Definition();
        $this->containerBuilder->setDefinition($registryServiceId, $registryDefinition);

        // Run the compiler pass
        $this->compilerPass->process($this->containerBuilder);

        // Retrieve the updated registry definition
        $updatedRegistryDefinition = $this->containerBuilder->getDefinition($registryServiceId);

        // Assertions
        self::assertTrue($this->containerBuilder->hasDefinition($registryServiceId));

        $arguments = $updatedRegistryDefinition->getArguments();
        self::assertArrayHasKey('$pdfDocumentOperatorLocator', $arguments);

        $serviceLocatorArgument = $arguments['$pdfDocumentOperatorLocator'];
        $serviceLocatorDefinitions = $serviceLocatorArgument->getValues();

        // Ensure the registry is empty
        self::assertEmpty($serviceLocatorDefinitions);
    }

    public function testProcessWithDuplicateTagsOverwritesPreviousDefinitions(): void
    {
        // Define the registry service
        $registryServiceId = 'oro_pdf_generator.pdf_document.operator.registry';
        $registryDefinition = new Definition();
        $this->containerBuilder->setDefinition($registryServiceId, $registryDefinition);

        // Add duplicate tagged services
        $operatorId1 = 'acme.operator.sample_1';
        $operatorId2 = 'acme.operator.sample_2';
        $this->containerBuilder
            ->setDefinition($operatorId1, new Definition())
            ->addTag(
                'oro_pdf_generator.pdf_document.operator',
                ['entity_class' => 'EntityClass1', 'mode' => 'instant']
            );
        $this->containerBuilder
            ->setDefinition($operatorId2, new Definition())
            ->addTag(
                'oro_pdf_generator.pdf_document.operator',
                ['entity_class' => 'EntityClass1', 'mode' => 'instant']
            );

        // Run the compiler pass
        $this->compilerPass->process($this->containerBuilder);

        // Retrieve the updated registry definition
        $updatedRegistryDefinition = $this->containerBuilder->getDefinition($registryServiceId);

        $arguments = $updatedRegistryDefinition->getArguments();
        self::assertArrayHasKey('$pdfDocumentOperatorLocator', $arguments);

        $serviceLocatorArgument = $arguments['$pdfDocumentOperatorLocator'];
        $serviceLocatorDefinitions = $serviceLocatorArgument->getValues();

        // Assertions
        self::assertArrayHasKey('EntityClass1', $serviceLocatorDefinitions);

        $entityClass1Locator = $serviceLocatorDefinitions['EntityClass1'];
        self::assertInstanceOf(Definition::class, $entityClass1Locator);

        $entityClass1Modes = $entityClass1Locator->getArgument(0);
        self::assertArrayHasKey('instant', $entityClass1Modes);

        // Ensure the last service definition overwrites the previous one
        $lastServiceReference = $entityClass1Modes['instant']->getValues()[0];
        self::assertSame($operatorId2, (string)$lastServiceReference);
    }
}
