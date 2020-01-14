<?php

namespace Oro\Bundle\AttachmentBundle\Guesser;

use Symfony\Component\Mime\MimeTypesInterface;

class MimeTypeExtensionGuesser implements MimeTypesInterface
{
    /** @var array */
    protected $mimeExtensionMap = [
        'application/vnd.ms-outlook' => ['msg'],
    ];

    /** @var array */
    protected $extensionMimeMap = [
        'msg' => ['application/vnd.ms-outlook'],
    ];

    /**
     * {@inheritdoc}
     */
    public function isGuesserSupported(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMimeType(string $path): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(string $mimeType): array
    {
        return $this->mimeExtensionMap[$mimeType] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeTypes(string $ext): array
    {
        return $this->extensionMimeMap[$ext] ?? [];
    }
}
