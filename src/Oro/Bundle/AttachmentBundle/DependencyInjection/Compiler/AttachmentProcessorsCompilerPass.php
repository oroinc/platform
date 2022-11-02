<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Responsible for configurations of pngquant, jpegoptim libraries.
 */
class AttachmentProcessorsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processorHelper = new ProcessorHelper($container->getParameterBag());
        $librariesExists = false;
        try {
            $librariesExists = $processorHelper->librariesExists();
        } catch (\Exception $exception) {
            // Any error in catch indicates that the library does not exist or its version does not meet the
            // needs of the system
        }

        // liip_imagine expects paths to be strings, null is not allowed as a default value, so we override it
        $PNGQuantLibraryPath = '';
        $JPEGOptimLibraryPath = '';
        if ($librariesExists) {
            $PNGQuantLibraryPath = $processorHelper->getPNGQuantLibrary();
            $JPEGOptimLibraryPath = $processorHelper->getJPEGOptimLibrary();
        }

        $container->setParameter('liip_imagine.pngquant.binary', $PNGQuantLibraryPath);
        $container->setParameter('liip_imagine.jpegoptim.binary', $JPEGOptimLibraryPath);
    }
}
