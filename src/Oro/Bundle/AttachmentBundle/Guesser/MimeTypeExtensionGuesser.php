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

    #[\Override]
    public function isGuesserSupported(): bool
    {
        return true;
    }

    #[\Override]
    public function guessMimeType(string $path): ?string
    {
        return null;
    }

    #[\Override]
    public function getExtensions(string $mimeType): array
    {
        return $this->mimeExtensionMap[$mimeType] ?? [];
    }

    #[\Override]
    public function getMimeTypes(string $ext): array
    {
        return $this->extensionMimeMap[$ext] ?? [];
    }
}
