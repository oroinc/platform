<?php

namespace Oro\Bundle\ImportExportBundle;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ConsumptionExtensionCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\FormatterProviderPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\MigrateFileStorageCommandCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ReaderCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TemplateEntityRepositoryCompilerPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\TypeValidationLoaderPass;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\WriterCompilerPass;
use Oro\Bundle\ImportExportBundle\MimeType\CsvMimeTypeGuesser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Mime\MimeTypes;

/**
 * ImportExport Bundle. Registers compiler passes. Adds MIME Type guesser.
 */
class OroImportExportBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypeValidationLoaderPass());
        $container->addCompilerPass(new AddNormalizerCompilerPass());
        $container->addCompilerPass(new ProcessorRegistryCompilerPass());
        $container->addCompilerPass(new TemplateEntityRepositoryCompilerPass());
        $container->addCompilerPass(new FormatterProviderPass());
        $container->addCompilerPass(new WriterCompilerPass());
        $container->addCompilerPass(new ReaderCompilerPass());
        $container->addCompilerPass(new ImportExportConfigurationRegistryCompilerPass());
        $container->addCompilerPass(new ConsumptionExtensionCompilerPass());
        $container->addCompilerPass(new MigrateFileStorageCommandCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        MimeTypes::getDefault()->registerGuesser(new CsvMimeTypeGuesser());
    }
}
