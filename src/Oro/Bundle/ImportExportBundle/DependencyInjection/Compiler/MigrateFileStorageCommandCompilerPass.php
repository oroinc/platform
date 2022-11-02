<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\Command\MigrateFileStorageCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds import-export file storage config to the oro:gaufrette:migrate-filestorages migration command.
 */
class MigrateFileStorageCommandCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition(MigrateFileStorageCommand::class)
            ->addMethodCall(
                'addMapping',
                ['/var/import_export', 'importexport']
            )
            ->addMethodCall(
                'addFileManager',
                ['importexport', new Reference('oro_importexport.file_manager')]
            );
    }
}
