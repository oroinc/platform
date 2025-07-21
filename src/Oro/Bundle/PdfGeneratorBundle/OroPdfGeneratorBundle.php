<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle;

use Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler\PdfDocumentOperatorRegistryPass;
use Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Compiler\PdfTemplateTwigEnvironmentPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPdfGeneratorBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PdfTemplateTwigEnvironmentPass());
        $container->addCompilerPass(new PdfDocumentOperatorRegistryPass());
    }
}
