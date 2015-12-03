<?php

namespace Oro\Bundle\ImportExportBundle;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroImportExportBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddNormalizerCompilerPass());
        $container->addCompilerPass(new ProcessorRegistryCompilerPass());
    }
}
