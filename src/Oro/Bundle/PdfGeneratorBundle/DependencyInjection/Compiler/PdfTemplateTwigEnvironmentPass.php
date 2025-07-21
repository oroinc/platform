<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines a separate TWIG environment for PDF template rendering by cloning the original "twig" service.
 */
class PdfTemplateTwigEnvironmentPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $pdfTemplateTwigServiceId = 'oro_pdf_generator.pdf_template_renderer.twig';
        $pdfTemplateTwigExtensionTag = 'oro_pdf_generator.pdf_template_renderer.twig.extension';

        if (!$container->hasDefinition('twig')) {
            return;
        }

        $originalTwigDefinition = $container->getDefinition('twig');
        $pdfTemplateTwigServiceDefinition = clone $originalTwigDefinition;

        $container->setDefinition($pdfTemplateTwigServiceId, $pdfTemplateTwigServiceDefinition);

        // Extensions must always be registered before everything else.
        // For instance, global variable definitions must be registered
        // afterward. If not, the globals from the extensions will never
        // be registered.
        $originalExtensionMethodCalls = $originalOtherMethodCalls = [];
        foreach ($pdfTemplateTwigServiceDefinition->getMethodCalls() as $methodCall) {
            if ($methodCall[0] === 'addExtension') {
                $originalExtensionMethodCalls[] = $methodCall;
            } else {
                $originalOtherMethodCalls[] = $methodCall;
            }
        }

        $newExtensionMethodCalls = [];
        foreach ($this->findAndSortTaggedServices($pdfTemplateTwigExtensionTag, $container) as $extension) {
            $newExtensionMethodCalls[] = ['addExtension', [$extension]];
        }

        if ($newExtensionMethodCalls) {
            $pdfTemplateTwigServiceDefinition->setMethodCalls(
                array_merge($originalExtensionMethodCalls, $newExtensionMethodCalls, $originalOtherMethodCalls)
            );
        }
    }
}
