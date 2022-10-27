<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\Command\MigrateFileStorageCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds attachments file storages configs to the oro:gaufrette:migrate-filestorages migration command.
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
                ['/var/attachment/protected_mediacache', 'protected_mediacache']
            )
            ->addMethodCall(
                'addMapping',
                ['/var/attachment', 'attachments']
            )
            ->addMethodCall(
                'addMapping',
                ['/public/media/cache', 'public_mediacache']
            )
            ->addMethodCall(
                'addMapping',
                ['/var/import_export/files', 'import_files']
            )
            ->addMethodCall(
                'addFileManager',
                ['protected_mediacache', new Reference('oro_attachment.manager.protected_mediacache')]
            )
            ->addMethodCall(
                'addFileManager',
                ['attachments', new Reference('oro_attachment.file_manager')]
            )
            ->addMethodCall(
                'addFileManager',
                ['public_mediacache', new Reference('oro_attachment.manager.public_mediacache')]
            )
            ->addMethodCall(
                'addFileManager',
                ['import_files', new Reference('oro_attachment.importexport.file_manager.import_files')]
            );
    }
}
