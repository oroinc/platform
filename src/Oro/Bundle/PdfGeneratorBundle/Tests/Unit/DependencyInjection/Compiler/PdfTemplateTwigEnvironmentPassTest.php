<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler\PdfTemplateTwigEnvironmentPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Extension\AbstractExtension;

final class PdfTemplateTwigEnvironmentPassTest extends TestCase
{
    private PdfTemplateTwigEnvironmentPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new PdfTemplateTwigEnvironmentPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessDoesNothingIfTwigServiceIsMissing(): void
    {
        $this->compilerPass->process($this->container);
        self::assertFalse($this->container->hasDefinition('oro_pdf_generator.pdf_template_renderer.twig'));
    }

    public function testProcessClonesTwigServiceAndAddsNewExtensions(): void
    {
        $twigDefinition = new Definition();
        $originalOtherMethodCall = ['name', 'value'];
        $twigDefinition->addMethodCall('addGlobal', $originalOtherMethodCall);
        $originalExtensionMethodCall = [$this->createMock(AbstractExtension::class)];
        $twigDefinition->addMethodCall('addExtension', $originalExtensionMethodCall);
        $this->container->setDefinition('twig', $twigDefinition);

        $rendererDefinition = new Definition();
        $this->container->setDefinition('oro_pdf_generator.pdf_template_renderer', $rendererDefinition);

        $pdfTwigExtensionDefinition = new Definition();
        $pdfTwigExtensionId = 'pdf_extension';
        $this->container
            ->setDefinition($pdfTwigExtensionId, $pdfTwigExtensionDefinition)
            ->addTag('oro_pdf_generator.pdf_template_renderer.twig.extension');

        $this->compilerPass->process($this->container);

        self::assertTrue($this->container->hasDefinition('oro_pdf_generator.pdf_template_renderer.twig'));

        $pdfTwigDefinition = $this->container->getDefinition('oro_pdf_generator.pdf_template_renderer.twig');

        $methodCalls = $pdfTwigDefinition->getMethodCalls();
        self::assertCount(3, $methodCalls);

        // Original extensions must always be registered before everything else.
        self::assertSame('addExtension', $methodCalls[0][0]);
        self::assertSame($originalExtensionMethodCall, $methodCalls[0][1]);

        // New extensions must always be registered before original ones.
        self::assertSame('addExtension', $methodCalls[1][0]);
        self::assertEquals(new Reference($pdfTwigExtensionId), $methodCalls[1][1][0]);

        // All other calls must always be registered last.
        self::assertSame('addGlobal', $methodCalls[2][0]);
        self::assertEquals($originalOtherMethodCall, $methodCalls[2][1]);
    }
}
