<?php

namespace Oro\Bundle\AttachmentBundle;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Compiler\AttachmentProcessorsCompilerPass;
use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;
use Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Mime\MimeTypes;

/**
 * Attachment Bundle. Adds MIME Type and MIME Type Extension guesser
 */
class OroAttachmentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $mimeTypes = MimeTypes::getDefault();
        $mimeTypes->registerGuesser(new MsMimeTypeGuesser());
        $mimeTypes->registerGuesser(new MimeTypeExtensionGuesser());
    }

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AttachmentProcessorsCompilerPass());
    }
}
