<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

/**
 * Contains handy functions for working with filename extension.
 */
class FilenameExtensionHelper
{
    public static function addExtension(string $filename, string $extension): string
    {
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

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }
}
