<?php

namespace Oro\Bundle\AttachmentBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
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
    #[\Override]
    public function boot(): void
    {
        parent::boot();

        $mimeTypes = MimeTypes::getDefault();
        $mimeTypes->registerGuesser(new MsMimeTypeGuesser());
        $mimeTypes->registerGuesser(new MimeTypeExtensionGuesser());
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AttachmentProcessorsCompilerPass());
        $container->addCompilerPass(new MigrateFileStorageCommandCompilerPass());
        $container->addCompilerPass(new AddSupportedFieldTypesCompilerPass());

        /** @var LiipImagineExtension $extension */
        $extension = $container->getExtension('liip_imagine');
        $extension->addResolverFactory(new GaufretteResolverFactory());

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAttributeMappingDriver(
                    ['Oro\Bundle\AttachmentBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }
}
