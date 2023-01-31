<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Responsible for configurations of pngquant, jpegoptim libraries.
 */
class AttachmentProcessorsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('liip_imagine.filter.post_processor.pngquant')
            ->setArgument(
                '$executablePath',
                new Expression("service('oro_attachment.processor_helper').getPNGQuantLibrary() ?: '/usr/bin/pngquant'")
            );
        $container->getDefinition('liip_imagine.filter.post_processor.jpegoptim')
            ->setArgument(
                '$executablePath',
                new Expression(
                    "service('oro_attachment.processor_helper').getJPEGOptimLibrary() ?: '/usr/bin/jpegoptim'"
                )
            );
    }
}
