<?php

namespace Oro\Bundle\AttachmentBundle\Guesser;

use Symfony\Component\Mime\MimeTypesInterface;

/**
 * Provides MIME type and file extension mapping for specialized file types.
 *
 * This guesser implements the {@see MimeTypesInterface} to provide custom MIME type to file extension
 * mappings for file types that may not be recognized by the standard MIME type guesser.
 * It maintains bidirectional mappings between MIME types and file extensions, allowing
 * the system to determine appropriate file extensions for specific MIME types and vice versa.
 * This is particularly useful for handling proprietary or less common file formats.
 */
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
