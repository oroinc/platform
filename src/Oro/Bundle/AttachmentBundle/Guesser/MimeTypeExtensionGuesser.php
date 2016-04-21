<?php

namespace Oro\Bundle\AttachmentBundle\Guesser;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

class MimeTypeExtensionGuesser implements ExtensionGuesserInterface
{
    /** @var array */
    protected $mimeExtensionMap = [
        'application/vnd.ms-outlook' => 'msg',
    ];

    /**
     * {@inheritdoc}
     */
    public function guess($mimeType)
    {
        return isset($this->mimeExtensionMap[$mimeType]) ? $this->mimeExtensionMap[$mimeType] : null;
    }
}
