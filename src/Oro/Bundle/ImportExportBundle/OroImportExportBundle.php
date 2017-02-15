<?php

namespace Oro\Bundle\ImportExportBundle;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\FormatterProviderPass;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\SplitterCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\WriterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroImportExportBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddNormalizerCompilerPass());
        $container->addCompilerPass(new ProcessorRegistryCompilerPass());
        $container->addCompilerPass(new TemplateEntityRepositoryCompilerPass());
        $container->addCompilerPass(new FormatterProviderPass());
        $container->addCompilerPass(new WriterCompilerPass());
        $container->addCompilerPass(new SplitterCompilerPass());
    }
}
