<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Symfony\Component\Mime\MimeTypes;

/**
 * Contains handy functions for working with filename extension.
 */
class FilenameExtensionHelper
{
    private array $unsupportedMimeTypes;

    public function __construct(array $unsupportedMimeTypes)
    {
        $this->unsupportedMimeTypes = $unsupportedMimeTypes;
    }

    public function addExtension(string $filename, string $extension, array $fileMimeTypes = []): string
    {
        if (empty($fileMimeTypes)) {
            $mimeTypeGuesser = new MimeTypes();
            $fileMimeTypes = $mimeTypeGuesser->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        }

        if (!empty(array_intersect($fileMimeTypes, $this->unsupportedMimeTypes))) {
            return $filename;
        }

        $extension = trim($extension);
        if ($extension === '') {
            return $filename;
        }

        $currentExtension = self::canonicalizeExtension(pathinfo($filename, PATHINFO_EXTENSION));
        if (self::canonicalizeExtension($extension) !== $currentExtension) {
            $filename .= '.' . $extension;
        }

        return $filename;
    }

    public static function canonicalizeExtension(string $extension): string
    {
        $extension = strtolower(trim($extension));

        return match ($extension) {
            'jpeg' => 'jpg',
            default => $extension
        };
    }
}
