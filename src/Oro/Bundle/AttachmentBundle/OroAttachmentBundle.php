<?php

namespace Oro\Bundle\AttachmentBundle;

use Liip\ImagineBundle\DependencyInjection\LiipImagineExtension;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler\AddSupportedFieldTypesCompilerPass;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler\AttachmentProcessorsCompilerPass;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler\MigrateFileStorageCommandCompilerPass;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Imagine\Factory\GaufretteResolverFactory;
use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;
use Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Mime\MimeTypes;

class OroAttachmentBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        parent::boot();

        $mimeTypes = MimeTypes::getDefault();
        $mimeTypes->registerGuesser(new MsMimeTypeGuesser());
        $mimeTypes->registerGuesser(new MimeTypeExtensionGuesser());
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AttachmentProcessorsCompilerPass());
        $container->addCompilerPass(new MigrateFileStorageCommandCompilerPass());
        $container->addCompilerPass(new AddSupportedFieldTypesCompilerPass());

        /** @var LiipImagineExtension $extension */
        $extension = $container->getExtension('liip_imagine');
        $extension->addResolverFactory(new GaufretteResolverFactory());
    }
}
