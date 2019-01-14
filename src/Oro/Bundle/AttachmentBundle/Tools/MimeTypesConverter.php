<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

/**
 * Provides static methods to convert the list of MIME types
 * from a string contains MIME types delimited by linefeed (\n),
 * carriage return + linefeed (\r\n) or comma (,) symbols
 * to an array and vise versa.
 * Linefeed (\n) is used as a delimiter for converting from an array to a string.
 */
final class MimeTypesConverter
{
    /**
     * Converts the list of MIME types from an array to a string representation.
     *
     * @param string[] $mimeTypes
     *
     * @return string
     */
    public static function convertToString(array $mimeTypes): string
    {
        return implode("\n", $mimeTypes);
    }

    /**
     * Converts the list of MIME types from a string representation to an array.
     *
     * @param string|null $mimeTypes
     *
     * @return string[]
     */
    public static function convertToArray(?string $mimeTypes): array
    {
        if (!$mimeTypes) {
            return [];
        }

        return array_map('trim', explode("\n", str_replace(',', "\n", str_replace("\r", '', $mimeTypes))));
    }
}
