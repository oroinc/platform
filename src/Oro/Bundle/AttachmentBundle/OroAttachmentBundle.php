<?php

namespace Oro\Bundle\AttachmentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

use Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser;
use Oro\Bundle\AttachmentBundle\Guesser\MimeTypeExtensionGuesser;

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
