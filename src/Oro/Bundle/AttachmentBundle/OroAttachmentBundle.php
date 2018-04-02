<?php

namespace Oro\Bundle\AttachmentBundle;

use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;
use Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroAttachmentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        MimeTypeGuesser::getInstance()->register(new MsMimeTypeGuesser());
        ExtensionGuesser::getInstance()->register(new MimeTypeExtensionGuesser());
    }
}
